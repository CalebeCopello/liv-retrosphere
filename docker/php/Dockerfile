FROM php:8.4-fpm-alpine AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer
ENV TZ=America/Sao_Paulo

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-data-full \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    linux-headers \
    oniguruma-dev \
    shadow \
    su-exec \
    unzip \
    zip \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN if getent group www-data > /dev/null 2>&1; then \
        groupmod -g "${GROUP_ID}" www-data; \
    else \
        addgroup -g "${GROUP_ID}" www-data; \
    fi \
    && usermod -u "${USER_ID}" -g "${GROUP_ID}" www-data

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-retro.ini
COPY docker/php/entrypoint.sh /usr/local/bin/retro-entrypoint

RUN chmod +x /usr/local/bin/retro-entrypoint

ENTRYPOINT ["retro-entrypoint"]

FROM base AS development

ENV APP_ENV=local
ENV APP_DEBUG=true

CMD ["php-fpm", "-F"]

FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY --chown=www-data:www-data . .

RUN composer dump-autoload \
        --no-dev \
        --optimize \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

USER www-data

CMD ["php-fpm", "-F"]