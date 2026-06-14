FROM composer:2 AS php-vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts


FROM node:22 AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./

RUN npm install --ignore-scripts

COPY . .

# bring in composer vendor from the php-vendor stage so imports like ../../vendor/... resolve
COPY --from=php-vendor /app/vendor ./vendor
RUN npm run build

# ---------------------
# PHP build stage
# ---------------------
FROM php:8.4-fpm

WORKDIR /var/www/app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    xml \
    bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --no-progress

COPY . .

RUN mkdir -p bootstrap/cache storage/framework/sessions \
     storage/framework/views \
     storage/framework/cache \
    && chown -R www-data:www-data bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage

COPY --from=frontend /app/public/build ./public/build

# do not run scripts automatically
RUN composer dump-autoload --optimize --no-scripts
# now run package discovery explicitly so failures are easier to inspect
RUN php artisan package:discover --ansi
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan key:generate

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD composer dump-autoload \
    && php artisan migrate --force \
    && php artisan optimize:clear \
    && php artisan serve --host=0.0.0.0 --port=8000