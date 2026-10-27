#!/bin/sh
set -e

cd /var/www/html

# O volume do Coolify montado em storage/app chega vazio e com dono root na
# primeira subida, então as pastas são recriadas e devolvidas para o www-data
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

artisan() {
    su-exec www-data php artisan "$@"
}

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    artisan migrate --force
fi

# O config:cache fica de fora: o código ainda chama env() direto fora de config/
# (APP_URL, AWESOME_API_*), e com a config cacheada essas chamadas voltam null
artisan route:cache
artisan event:cache
artisan view:cache

exec supervisord -c /etc/supervisord.conf
