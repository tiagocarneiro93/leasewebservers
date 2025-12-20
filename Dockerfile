# Stage 1: Build Angular Frontend
FROM node:20-alpine AS frontend-builder

WORKDIR /app/frontend

# Copy package files
COPY frontend/package*.json ./

# Install dependencies
RUN npm ci --legacy-peer-deps

# Copy source code
COPY frontend/ ./

# Build for production
RUN npm run build -- --configuration=production

# Stage 2: Build PHP Backend
FROM composer:2 AS backend-builder

WORKDIR /app/backend

# Copy composer files
COPY backend/composer.json backend/composer.lock ./

# Install dependencies without dev
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy source code
COPY backend/ ./

# Finish composer install
RUN composer dump-autoload --optimize --classmap-authoritative

# Stage 3: Production Image
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libzip-dev \
    icu-dev \
    sqlite-dev \
    && docker-php-ext-install \
    pdo_sqlite \
    zip \
    intl \
    opcache \
    && rm -rf /var/cache/apk/*

# PHP configuration for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/php.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/php.ini

# Create working directory
WORKDIR /var/www/html

# Copy backend from builder
COPY --from=backend-builder /app/backend /var/www/html

# Copy frontend build to public directory
COPY --from=frontend-builder /app/frontend/dist/frontend/browser /var/www/html/public/app

# Create var directory with proper permissions
RUN mkdir -p /var/www/html/var \
    && chmod -R 777 /var/www/html/var

# Nginx configuration
COPY docker/nginx/production.conf /etc/nginx/http.d/default.conf

# Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Create startup script with better error handling
RUN echo '#!/bin/sh' > /start.sh \
    && echo 'set -e' >> /start.sh \
    && echo 'echo "Starting Leaseweb Server Explorer..."' >> /start.sh \
    && echo 'echo "Creating database..."' >> /start.sh \
    && echo 'php bin/console doctrine:database:create --if-not-exists --no-interaction || echo "Database exists"' >> /start.sh \
    && echo 'echo "Updating schema..."' >> /start.sh \
    && echo 'php bin/console doctrine:schema:update --force --no-interaction' >> /start.sh \
    && echo 'echo "Loading fixtures..."' >> /start.sh \
    && echo 'php bin/console doctrine:fixtures:load --no-interaction --append || php bin/console doctrine:fixtures:load --no-interaction' >> /start.sh \
    && echo 'echo "Warming up cache..."' >> /start.sh \
    && echo 'php bin/console cache:warmup --env=prod --no-debug' >> /start.sh \
    && echo 'echo "Starting services..."' >> /start.sh \
    && echo 'exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /start.sh \
    && chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]

