FROM php:8.4-cli

WORKDIR /var/www/app

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl libpq-dev libonig-dev libxml2-dev zip unzip nodejs npm \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring xml bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy source
COPY . .

# Install PHP + JS deps and build frontend assets
RUN composer install --no-interaction --prefer-dist \
    && npm install --ignore-scripts \
    && npm run build

# Copy env and generate key
RUN cp .env.example .env \
    && sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/' .env \
    && sed -i 's/DB_PASSWORD=/DB_PASSWORD=secret/' .env \
    && sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=file/' .env \
    && php artisan key:generate

EXPOSE 8000

CMD composer dump-autoload \
    && php artisan migrate --force \
    && php artisan optimize:clear \
    && php artisan db:seed --class="Database\Seeders\DatabaseSeeder" --force \
    && php artisan serve --host=0.0.0.0 --port=8000
