# Dockerfile para PHP
FROM php:8.1-apache

# Instalar dependencias necesarias para Laravel y la extensión zip
RUN apt-get update \
    && apt-get install -y git unzip zip libzip-dev \
    && docker-php-ext-install zip pdo_mysql \
    # Instalar Composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar Apache para que el DocumentRoot sea /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/html>|<Directory /var/www/html/public>|g' /etc/apache2/apache2.conf

EXPOSE 80
