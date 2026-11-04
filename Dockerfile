FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y \
        git curl zip unzip libzip-dev libpq-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip gd \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install \
    && npm run build \
    && npm cache clean --force

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD php artisan migrate --force && php artisan serve --host 0.0.0.0 --port ${PORT:-8080}
