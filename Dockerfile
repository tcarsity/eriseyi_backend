# Use PHP 8.2 with fpm
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    unzip \
    git \
    libpq-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install app dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Add Laravel permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose Render port
EXPOSE 10000

# Use ENTRYPOINT instead of CMD for migrations
ENTRYPOINT ["entrypoint.sh"]

# Start PHP server (Render will expose port 10000)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
