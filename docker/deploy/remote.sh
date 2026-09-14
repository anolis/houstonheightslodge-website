#!/usr/bin/env bash
set -euo pipefail
deploy_sha=$1
release_tmp=$2
[[ "$deploy_sha" =~ ^[0-9a-f]{40}$ ]] || exit 2
[[ "$release_tmp" =~ ^/tmp/lodge-deploy\.[a-zA-Z0-9_]+$ ]] || exit 2
cd "${DEPLOY_ROOT:-/var/www/website}"
# Serializes deployments from GitHub Actions and every developer laptop.
lock_file=$(git rev-parse --git-path lodge-deploy.lock)
exec 9>"$lock_file"
flock -w "${DEPLOY_LOCK_TIMEOUT:-600}" 9
test "$(git branch --show-current)" = main
test -z "$(git status --porcelain)" || { echo 'Production checkout has uncommitted changes.' >&2; exit 1; }
git fetch origin main
if [[ "$(git rev-parse origin/main)" != "$deploy_sha" ]]; then
    echo 'Refusing to deploy: requested commit is no longer the current origin/main.' >&2
    exit 1
fi
git merge-base --is-ancestor HEAD "$deploy_sha"
mkdir "$release_tmp/build"
tar -xzf "$release_tmp/build.tgz" -C "$release_tmp/build"
test -s "$release_tmp/build/manifest.json"
php artisan down --retry=60
trap 'php artisan up' EXIT
git merge --ff-only "$deploy_sha"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
mkdir -p public/build
rsync -a --delete "$release_tmp/build/" public/build/
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan up
trap - EXIT
