#!/usr/bin/env bash
set -e

# Ensure storage and cache directories are writable
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run database migrations in production environment
if [ "${APP_ENV}" = "production" ]; then
  echo "[entrypoint] Running database migrations..."
  php artisan migrate --force || echo "[entrypoint] WARNING: migrate failed (check logs), continuing startup"
fi

echo "[entrypoint] Starting Laravel HTTP server..."
exec php artisan serve --host=0.0.0.0 --port=10000
