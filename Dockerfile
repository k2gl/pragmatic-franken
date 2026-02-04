# ============================================================================
# STAGE 1: Base - System dependencies and PHP extensions
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

# ============================================================================
# STAGE 2: Build - Composer dependencies (production)
# ============================================================================
FROM php_base AS php_builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --optimize-autoloader

# ============================================================================
# STAGE 3: Development - Local development environment
# ============================================================================
FROM php_base AS php_dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=develop
ENV SERVER_NAME="localhost"

RUN install-php-extensions xdebug

COPY ./docker/php/xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-scripts --prefer-dist || true

COPY . .

EXPOSE 80
EXPOSE 443
EXPOSE 443/udp
EXPOSE 9003

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# ============================================================================
# STAGE 4: Production - Final optimized image
# ============================================================================
FROM php_base AS php_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME="Runtime\\FrankenPhp\\Symfony\\Runtime"

COPY --from=php_builder /app/vendor /app/vendor

COPY config config/
COPY public public/
COPY src src/

RUN mkdir -p templates translations var

COPY ./docker/php/prod-optimizations.ini $PHP_INI_DIR/conf.d/

RUN chmod -R 755 config/

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]

EXPOSE 80
EXPOSE 443
EXPOSE 443/udp

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
