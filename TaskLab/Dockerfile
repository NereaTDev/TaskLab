# Stage 1: build frontend assets con Vite
FROM node:20 AS frontend

WORKDIR /app

COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources

RUN npm ci
RUN npm run build

# Stage 2: PHP-FPM + nginx (servidor concurrente, compatible con Render)
FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OPcache: evita que PHP reparsee archivos en cada request (contenedor inmutable = sin timestamps)
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.revalidate_freq=0"; \
    echo "opcache.interned_strings_buffer=16"; \
} > /usr/local/etc/php/conf.d/opcache-prod.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# nginx: quitar el default y poner el nuestro (escucha en 10000)
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx.conf /etc/nginx/conf.d/app.conf

EXPOSE 10000

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
