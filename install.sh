#!/usr/bin/env bash
#
# Dev-environment installer for the Houston Heights Lodge public website.
#
# Self-contained: bootstraps a working local instance from a fresh clone.
# It does NOT touch production and needs no VPN/Tailscale, SSH access, or
# shared secrets — everything runs locally against a fresh SQLite database.
#
# Usage:  ./install.sh
#
set -euo pipefail
cd "$(dirname "$0")"

info() { printf '\033[1;34m==>\033[0m %s\n' "$1"; }
warn() { printf '\033[1;33m !\033[0m %s\n' "$1"; }

# --- 1. Prerequisites -------------------------------------------------------
info "Checking prerequisites (PHP 8.3+, Composer, Node 20+, npm)..."
missing=0
for c in php composer node npm; do
  command -v "$c" >/dev/null 2>&1 || { warn "$c not found"; missing=1; }
done
if [ "$missing" = 1 ]; then
  echo "Install the missing tools, then re-run ./install.sh"
  exit 1
fi
php -r 'exit(version_compare(PHP_VERSION,"8.3.0",">=")?0:1);' \
  || { warn "PHP 8.3+ required (found $(php -r 'echo PHP_VERSION;'))"; exit 1; }

# --- 2. Environment file ----------------------------------------------------
info "Setting up .env..."
if [ ! -f .env ]; then
  cp .env.example .env
  echo "    created .env from .env.example"
else
  echo "    .env already exists — leaving it untouched"
fi

# --- 3. PHP dependencies ----------------------------------------------------
info "Installing PHP dependencies (composer)..."
composer install

# --- 4. App key -------------------------------------------------------------
if ! grep -q '^APP_KEY=base64:' .env; then
  info "Generating application key..."
  php artisan key:generate
fi

# --- 5. JS dependencies -----------------------------------------------------
info "Installing JS dependencies (npm)..."
if [ -f package-lock.json ]; then npm ci; else npm install; fi

# --- 6. Database (SQLite for local dev) -------------------------------------
if grep -q '^DB_CONNECTION=sqlite' .env; then
  info "Preparing SQLite database..."
  mkdir -p database
  [ -f database/database.sqlite ] || { touch database/database.sqlite; echo "    created database/database.sqlite"; }
fi
info "Running migrations..."
php artisan migrate --no-interaction

# --- 7. Storage symlink -----------------------------------------------------
if [ ! -e public/storage ]; then
  info "Linking storage..."
  php artisan storage:link
fi

# --- 8. Front-end build -----------------------------------------------------
info "Building front-end assets (vite)..."
npm run build

# --- Done -------------------------------------------------------------------
cat <<'EOF'

✅ Website dev environment ready.

  Start it:   php artisan serve        # http://127.0.0.1:8000
  Run tests:  php artisan test
  Lint:       vendor/bin/pint

Notes:
  • Mail uses the 'log' driver, so any outbound mail is written to
    storage/logs/laravel.log instead of being sent.

See the README for how to ship a change to production.
EOF
