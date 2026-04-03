FROM php:7.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libsnmp-dev \
    snmp \
    zip \
    unzip \
    net-tools \
    telnet \
    libzip-dev \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    sockets \
    snmp \
    zip \
    && pecl install redis \
    && docker-php-ext-enable redis opcache

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files (optimized for Docker layer caching)
COPY composer.json composer.lock* ./
COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY database/ ./database/
COPY public/ ./public/
COPY resources/ ./resources/
COPY routes/ ./routes/
COPY artisan ./

# Install dependencies with optimization
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative

# Copy remaining files
COPY . /var/www

# Generate optimized autoload files
RUN composer dump-autoload --optimize --classmap-authoritative

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/bootstrap/cache

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
