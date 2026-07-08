FROM php:8.4-fpm

# Instala dependências do sistema e libexif necessária para a extensão exif
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev libexif-dev

# Instala as extensões PHP (Adicionado 'exif' na lista)
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl exif && pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretório de trabalho
WORKDIR /var/www/html