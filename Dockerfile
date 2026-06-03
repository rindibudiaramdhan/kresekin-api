FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY artisan ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM node:24-alpine AS assets

WORKDIR /app

COPY package.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm install && npm run build

FROM php:8.4-cli-alpine AS runtime

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        libpq \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        postgresql-dev \
        libzip-dev \
    && docker-php-ext-install \
        opcache \
        pdo_pgsql \
        zip \
    && apk del .build-deps

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY . .

RUN mkdir -p storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
