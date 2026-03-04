FROM php:8.3-cli

# Working directory
WORKDIR /var/www/html

# System dependencies for Composer and PostgreSQL + common Laravel extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Allow composer to run as root inside the container
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy application code
COPY . .

# Install PHP dependencies and optimize autoloader
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Cache config, routes and views
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Expose port used by artisan serve
EXPOSE 10000

# Start Laravel using the built-in server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
