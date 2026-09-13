#!/bin/sh
set -eu
cp .env.example .env
php artisan key:generate --force
touch database/database.sqlite
php artisan migrate --force
chown -R www-data:www-data storage bootstrap/cache database
exec "$@"
