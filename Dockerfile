# Despliegue de 02 §4. Bases multi-arquitectura (amd64 y arm64): no fijar --platform.

# Etapa 1: assets de Vite (CSS, JS y fuentes) en public/build.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# Etapa 2: FrankenPHP sirviendo HTTP en :8000 detrás de Caddy.
FROM dunglas/frankenphp:1-php8.4
WORKDIR /app

RUN install-php-extensions pdo_pgsql gd intl zip opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    # El contenedor corre con cap_drop ALL: un binario con capacidades de archivo no arranca,
    # y en :8000 no hace falta cap_net_bind_service.
    && setcap -r /usr/local/bin/frankenphp

COPY docker/php/uploads.ini "$PHP_INI_DIR/conf.d/uploads.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan storage:link \
    && chown -R 1001:1001 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN chmod 0755 /usr/local/bin/laravel-entrypoint

ENV SERVER_NAME=:8000
EXPOSE 8000

ENTRYPOINT ["laravel-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
