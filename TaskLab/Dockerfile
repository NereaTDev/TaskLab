# Stage 1: build frontend assets with Vite
FROM node:20 AS frontend

WORKDIR /app

COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources

RUN npm ci
RUN npm run build

# Stage 2: FrankenPHP — servidor concurrente, reemplaza php artisan serve
FROM dunglas/frankenphp:1-php8.4

WORKDIR /var/www/html

# Instalar extensiones PHP necesarias (install-php-extensions incluido en la imagen)
RUN install-php-extensions pdo pdo_pgsql mbstring zip opcache

# Configurar OPcache para producción (no revalida timestamps: contenedor inmutable)
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.revalidate_freq=0"; \
    echo "opcache.interned_strings_buffer=16"; \
} > /usr/local/etc/php/conf.d/opcache-prod.ini

# Instalar Composer desde imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Copiar la app completa de Laravel
COPY . .

# Copiar assets compilados del stage 1
COPY --from=frontend /app/public/build ./public/build

# Instalar dependencias PHP en modo producción y limpiar caches de dev
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Permisos de escritura para storage y bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copiar Caddyfile personalizado (escucha en puerto 10000, sirve estáticos directamente)
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 10000

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
