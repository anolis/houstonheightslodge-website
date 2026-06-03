# Houston Heights Lodge Website

Public Laravel site for Houston Heights Lodge #225.

- Live URL: https://website.houstonheightslodge225.com
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

## Deploy Notes

Typical update flow on `lodge`:

```bash
cd /var/www/website
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan test
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
