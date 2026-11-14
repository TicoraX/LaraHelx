#!/bin/sh
set -eu

# storage/framework es un tmpfs vacío: sin estas carpetas Blade no tiene
# ruta de caché y ninguna vista renderiza.
mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions

php artisan optimize

exec docker-php-entrypoint "$@"
