FROM php:8.4-cli
WORKDIR /var/www/app

RUN apt-get update && apt-get install -y \
    git curl libpq-dev libonig-dev libxml2-dev zip unzip nodejs npm \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql mbstring xml bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN cp .env.example .env \
    && sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/' .env \
    && sed -i 's/DB_PASSWORD=/DB_PASSWORD=secret/' .env \
    && sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/' .env

RUN mkdir -p storage/framework/views \
    storage/framework/cache \
    storage/framework/sessions \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-interaction --prefer-dist --no-scripts \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi

RUN php artisan key:generate

RUN npm install --ignore-scripts \
    && npm run build

EXPOSE 8000

CMD php artisan migrate --force \
    && php artisan db:seed --class="Database\Seeders\DatabaseSeeder" --force \
    && php artisan optimize:clear \
    && php artisan serve --host=0.0.0.0 --port=8000
