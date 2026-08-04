# Houston Heights Lodge Website

Public Laravel site for Houston Heights Lodge #225.

- Live URL: https://houstonheightslodge225.com (and `www.`; `website.` 301-redirects here)
- Server path: `/var/www/website`
- Web root: `/var/www/website/public`
- Production server: `lodge`
- Repository: `git@github.com:anolis/houstonheightslodge-website.git`

## Overview

This app is the Laravel port of the former static/PHP site in `/var/www/html`. The old page fragments were copied into Blade views and are rendered by Laravel routes instead of the old AJAX shell.

Key files:

- `routes/web.php`: public page routes, metadata map, and downloads routes.
- `resources/views/layouts/public.blade.php`: shared layout, navigation, footer, analytics, and the five-tap downloads shortcut on the site title.
- `resources/views/pages/*.blade.php`: page content migrated from `res/pages/*.html`.
- `resources/views/downloads/index.blade.php`: downloads listing page.
- `public/res`: static images, CSS, JavaScript, and Lightbox assets copied from the old site.
- `downloads`: server-local downloadable files. Contents are ignored by git.

## Downloads

APK files live in the project-level `downloads/` directory, not under `public/`.

The `/downloads` route lists `*.apk` files from that directory, and `/downloads/{filename}` streams matching APK files through Laravel.

Git behavior:

- `downloads/.gitkeep` is tracked so the directory exists after clone.
- `downloads/*` is ignored so APK files are not committed.

## Legacy API

The members page still calls legacy endpoints under `public/api`:

- `check-auth.php`
- `send-otp.php`
- `verify-otp.php`
- `logout.php`

The private config file is intentionally ignored:

- `public/api/_config.php`

Keep that file on the server, but do not commit it.

## Local development setup

Get a working local instance from a fresh clone with the installer. It is
**self-contained** — no VPN/Tailscale, production access, or shared secrets
required; everything runs locally against a fresh SQLite database:

```bash
./install.sh
```

It checks prerequisites (PHP 8.3+, Composer, Node 20+), installs PHP + JS
dependencies, creates `.env` and an app key, sets up a fresh SQLite database,
runs migrations, links storage, and builds assets. Then start it:

```bash
php artisan serve     # http://127.0.0.1:8000
```

Mail uses the `log` driver locally, so any outbound mail is written to
`storage/logs/laravel.log` instead of being sent.

## Apache

The production vhost points `website.houstonheightslodge225.com` at Laravel's public directory.

Relevant Apache site files on `lodge`:

- `/etc/apache2/sites-available/website.houstonheightslodge225.com.conf`
- `/etc/apache2/sites-available/website.houstonheightslodge225.com-le-ssl.conf`

After changing Apache config:

```bash
sudo /usr/sbin/apache2ctl configtest
sudo systemctl reload apache2
```

Certbot manages the HTTPS certificate and renewal.

## Local Server Check

From `/var/www/website` on `lodge`:

```bash
php artisan serve --host=127.0.0.1 --port=8099
```

Then test sample routes:

```bash
curl -I http://127.0.0.1:8099/
curl -I http://127.0.0.1:8099/about
curl -I http://127.0.0.1:8099/downloads
```

## Tests

Run the Laravel test suite:

```bash
php artisan test
```

## Shipping a change to production

Deploys are **automated** via GitHub Actions (`.github/workflows/ci.yml` and
`deploy.yml`). You never SSH in to deploy.

1. **Branch, change, and open a PR:**
   ```bash
   git checkout -b my-change
   # ...edit, commit...
   git push -u origin my-change
   gh pr create            # or open the PR on GitHub
   ```
2. **CI runs on the PR** — PHPUnit (PHP 8.3/8.4), Pint code style, and a
   front-end build. Keep it green; run the same checks locally first:
   ```bash
   vendor/bin/pint --test     # style (run `vendor/bin/pint` to auto-fix)
   php artisan test
   ```
3. **Merge to `main`.** That triggers the **Deploy** workflow on the self-hosted
   runner:
   - CI runs again as a gate, then
   - CI publishes the tested Vite bundle; **staging** installs it and runs HTTP
     smoke checks, then — only if that passes —
   - **production** (`/var/www/website` on `lodge`, reached over Tailscale) is
     updated at the exact `main` SHA: Composer installs the locked PHP dependencies,
     the same tested frontend bundle is installed, migrations run, and post-deploy
     HTTP smoke checks must pass.

Watch a deploy with `gh run watch` or in the repo's **Actions** tab.

### Where the CI/CD runner runs

The `ci` jobs run on GitHub-hosted `ubuntu-latest`. The `deploy-staging` and
`deploy-prod` jobs run on a **self-hosted runner** (`runs-on: [self-hosted,
staging]`):

- The runner for this repo is **`vbox-website`**; a sibling runner
  **`vbox-portal`** serves the admin-portal repo. Both run inside a **single
  VirtualBox VM** — `portal-staging` (guest hostname `vbox`) — on the **`bb`**
  desktop (Pop!_OS). This VM was migrated off the old laptop in July 2026.
- That same VM hosts the **staging** environment (Apache/PHP at
  `/var/www/website`); the runner's work dir is `~/runner-website`.
- Networking: the VM is NAT'd with port-forwards on `bb`'s loopback — SSH
  `8022→22`, HTTP `8080→80`, HTTPS `8443→443`. Reach it with `ssh
  portal-staging` (routes through `bb` via `ProxyJump` from elsewhere; direct on
  `bb`). The staging site on `bb` is `http://127.0.0.1:8080`.
- `deploy-prod` runs from that runner and SSHes to production (`lodge`) over
  **Tailscale**.

**If a deploy is stuck or fails before CI even reaches staging:**

1. Is the VM up? On `bb`: `VBoxManage list runningvms` (start it with
   `VBoxManage startvm portal-staging --type headless`).
2. Is the runner online? In the VM:
   `systemctl status 'actions.runner.*website*'` — or GitHub → repo **Settings →
   Actions → Runners**.
3. The staging **build** step (`npm run build`) fetches web fonts over the
   network at build time; a network blip there can fail the deploy. (The failed
   deploys in July 2026 were transient font-fetch timeouts on the old laptop's
   network and no longer recur on `bb`.)

### Manual deploy (fallback)

Only if Actions is unavailable — by hand on `lodge`:

```bash
cd /var/www/website
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

Writable runtime paths must be writable by both the SSH user and the web server group:

```bash
sudo chown -R admin:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 2775 {} +
find storage bootstrap/cache -type f -exec chmod 0664 {} +
```

## Git Ignore Policy

Do not commit:

- `.env`
- `vendor/`
- `database/database.sqlite`
- runtime files under `storage/` and `bootstrap/cache/`
- APK files under `downloads/`
- `public/api/_config.php`
