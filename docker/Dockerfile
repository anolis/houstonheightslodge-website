# syntax=docker/dockerfile:1
ARG FRONTEND_SOURCE=frontend-built

FROM php:8.3-apache-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libicu-dev libzip-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install -j2 intl zip mbstring pdo_sqlite bcmath pcntl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
ENV COMPOSER_ALLOW_SUPERUSER=1

FROM php-base AS dependencies
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-progress --no-scripts

FROM dependencies AS test
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --optimize
CMD ["sh", "-ec", "cp .env.example .env; php artisan key:generate; php artisan test; vendor/bin/pint --test"]

FROM dependencies AS production-dependencies
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

FROM node:22-bookworm-slim AS frontend-built
WORKDIR /src
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY . .
RUN npm run build && mkdir /build && cp -a public/build/. /build/

FROM scratch AS frontend-prebuilt
COPY public/build/ /build/

FROM ${FRONTEND_SOURCE} AS frontend

FROM php-base AS staging
COPY . .
RUN rm -rf public/build
COPY --from=production-dependencies /var/www/html/vendor ./vendor
COPY --from=frontend /build/ ./public/build/
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --no-dev --optimize
ENV APP_ENV=staging APP_DEBUG=false LOG_CHANNEL=stderr MAIL_MAILER=log
COPY docker/staging-entrypoint.sh /usr/local/bin/staging-entrypoint
ENTRYPOINT ["staging-entrypoint"]
CMD ["apache2-foreground"]

FROM alpine:3.22 AS deploy-client
RUN apk add --no-cache bash openssh-client curl && mkdir -p /run/deploy
COPY docker/deploy/ /opt/deploy/
ENTRYPOINT ["bash", "/opt/deploy/client.sh"]
