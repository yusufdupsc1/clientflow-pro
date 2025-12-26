# Stage 1: Vendor Install
FROM composer:2.7 as vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-ansi \
    --no-scripts

# Stage 2: Frontend Build
FROM node:20 as frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Production Image
FROM php:8.3-fpm as production

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    mariadb-client \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy php.ini config
COPY ./docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Copy source code
COPY . .

# Copy dependencies from stages
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=frontend /app/public/build /var/www/html/public/build

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 9000

# Start command
CMD ["php-fpm"]
