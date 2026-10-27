# Imagem de produção (Coolify): nginx + php-fpm + Horizon + Reverb no mesmo
# container, gerenciados pelo supervisord. Banco, Redis, LibreTranslate e afins
# ficam de fora, como serviços separados.
# Para desenvolvimento local, o docker-compose usa o Dockerfile.dev.

FROM php:8.4-fpm-alpine AS base

# Instala as extensões já com as libs de runtime certas (gd com jpeg/webp para as
# conversões de mídia, pcntl/redis para o Horizon)
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache nginx supervisor su-exec \
    && install-php-extensions pdo_pgsql redis zip pcntl exif gd opcache

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/uploads.ini docker/php/opcache.ini "$PHP_INI_DIR/conf.d/"
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zzz-app.conf

WORKDIR /var/www/html


# Dependências do Composer numa etapa à parte, para a imagem final não levar o
# composer nem o cache dele
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

# Os scripts do composer rodam o package:discover, que gera o bootstrap/cache só
# com os pacotes de produção
RUN composer dump-autoload --optimize --no-dev --no-interaction


FROM base

COPY --from=vendor /var/www/html /var/www/html
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && nginx -t

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
