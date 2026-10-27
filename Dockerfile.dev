FROM php:8.4-fpm

# UID/GID do usuário do host, para que os arquivos criados dentro do
# container (migrations, cache, vendor...) pertençam ao seu usuário.
ARG UID=1000
ARG GID=1000

# Instala dependências do sistema e libexif necessária para a extensão exif
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev libexif-dev

# Instala as extensões PHP (Adicionado 'exif' na lista)
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl exif && pecl install redis && docker-php-ext-enable redis

# Limites de upload (a imagem base usa 2M/8M, menor que o permitido na validação)
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cria o usuário "app" espelhando o UID/GID do host.
# A imagem php-fpm já traz www-data com uid 33; se o UID/GID pedido já
# existir, apenas reaproveitamos o usuário/grupo existente.
RUN if ! getent group "${GID}" > /dev/null; then groupadd -g "${GID}" app; fi \
    && if ! getent passwd "${UID}" > /dev/null; then \
         useradd -u "${UID}" -g "${GID}" -m -s /bin/bash app; \
       fi \
    && mkdir -p /var/www/html /var/www/.composer \
    && chown -R "${UID}:${GID}" /var/www

# Composer precisa de um HOME gravável para cache/config
ENV COMPOSER_HOME=/var/www/.composer

# Diretório de trabalho
WORKDIR /var/www/html

USER ${UID}:${GID}
