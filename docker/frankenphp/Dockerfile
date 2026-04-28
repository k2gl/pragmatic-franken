# syntax=docker/dockerfile:1.7

# ============================================================================
# STAGE 1: Base — system dependencies and PHP extensions
# ============================================================================
FROM dunglas/frankenphp:1-php8.5-alpine AS php_base

WORKDIR /app

RUN apk add --no-cache \
        unzip \
        git \
    && install-php-extensions \
        intl \
        bcmath \
        zip \
        pdo_pgsql \
        apcu \
        opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1

# ============================================================================
# STAGE 2: Builder — production composer dependencies (no dev)
# ============================================================================
FROM php_base AS php_builder

# composer.lock is optional: copy it when present so builds are reproducible.
COPY composer.json composer.lock* ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --optimize-autoloader \
        --prefer-dist \
        --no-interaction

# ============================================================================
# STAGE 3: Development — local dev environment (with Xdebug)
# ============================================================================
FROM php_base AS php_dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=develop
ENV SERVER_NAME="localhost"

RUN install-php-extensions xdebug

COPY ./docker/php/xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

# Install dependencies (dev included). The full source is mounted at runtime
# via docker-compose, so we don't COPY the project tree here.
COPY composer.json composer.lock* ./

RUN composer install --no-scripts --prefer-dist --no-interaction || \
    composer update --no-scripts --prefer-dist --no-interaction

EXPOSE 80
EXPOSE 443
EXPOSE 443/udp
EXPOSE 9003

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# ============================================================================
# STAGE 4: Production — final optimized image
# ============================================================================
FROM php_base AS php_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME="Runtime\\FrankenPhp\\Symfony\\Runtime"

COPY --from=php_builder /app/vendor /app/vendor

COPY config config/
COPY public public/
COPY src src/

RUN mkdir -p templates translations var \
 && chmod -R 755 config/

COPY ./docker/php/prod-optimizations.ini $PHP_INI_DIR/conf.d/

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

EXPOSE 80
EXPOSE 443
EXPOSE 443/udp

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
