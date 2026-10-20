FROM php:8.2-fpm

WORKDIR /var/www/html

# Install system dependencies required by Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    --install-dir=/usr/local/bin --filename=composer

# Copy application source code
COPY . /var/www/html

# Install PHP dependencies (no dev packages, optimized autoloader)
RUN composer install --no-dev --optimize-autoloader

# Expose port that Render will forward to
EXPOSE 8000

# Start built‑in PHP server serving the public directory
CMD ["php","-S","0.0.0.0:8000","-t","public"]
