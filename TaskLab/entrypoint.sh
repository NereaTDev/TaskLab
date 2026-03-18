#!/usr/bin/env bash
set -e

php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true
php artisan storage:link  || true

if [ "${APP_ENV}" = "production" ]; then
    echo "[entrypoint] Running database migrations..."
    php artisan migrate --force || echo "[entrypoint] WARNING: migrate failed (check logs), continuing startup"

    echo "[entrypoint] Caching config, routes and views for production..."
    php artisan config:cache || echo "[entrypoint] WARNING: config:cache failed, continuing"
    php artisan route:cache  || echo "[entrypoint] WARNING: route:cache failed, continuing"
    php artisan view:cache   || echo "[entrypoint] WARNING: view:cache failed, continuing"
fi

echo "[entrypoint] Starting PHP-FPM..."
php-fpm --daemonize

echo "[entrypoint] Starting nginx..."
exec nginx -g 'daemon off;'
