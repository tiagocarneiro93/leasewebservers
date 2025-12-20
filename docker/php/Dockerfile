FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    sqlite-dev \
    && docker-php-ext-install \
    pdo_sqlite \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# PHP configuration
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Create var directory with proper permissions
RUN mkdir -p /var/www/html/var && chmod -R 777 /var/www/html/var

EXPOSE 9000

CMD ["php-fpm"]

