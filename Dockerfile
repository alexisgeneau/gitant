# ─── Stage 1: PHP dependencies ───────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS composer

WORKDIR /app

RUN apk add --no-cache \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# ─── Stage 2: Node / asset build ─────────────────────────────────────────────
FROM node:20-alpine AS node

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/

RUN npm run build

# ─── Stage 3: Dev image (with devDependencies + hot reload) ──────────────────
FROM php:8.3-fpm-alpine AS dev

WORKDIR /var/www/html

RUN apk add --no-cache \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
RUN composer install --no-interaction
RUN npm ci

EXPOSE 9000
CMD ["php-fpm"]

# ─── Stage 4: Production image ───────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS production

WORKDIR /var/www/html

RUN apk add --no-cache \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

COPY . .
COPY --from=composer /app/vendor ./vendor
COPY --from=node /app/public/build ./public/build

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
