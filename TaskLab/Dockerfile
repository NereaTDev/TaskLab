# Stage 1: build frontend assets with Vite
FROM node:20 AS frontend

WORKDIR /app

# Copy only files needed for frontend build
COPY package*.json vite.config.js postcss.config.js ./
COPY resources ./resources

RUN npm ci
RUN npm run build

# Stage 2: PHP + Laravel
FROM php:8.3-cli

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy full Laravel app
COPY . .

# Copy built frontend assets from the first stage
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --prefer-dist --optimize-autoloader

EXPOSE 10000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
