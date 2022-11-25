# Dockerfile para PHP
FROM php:8.1-apache

# Instalar dependencias necesarias para Laravel y la extensión zip
RUN apt-get update \
    && apt-get install -y git unzip zip libzip-dev \
    && docker-php-ext-install zip pdo_mysql \
    # Instalar Composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    # Habilitar mod_rewrite para que .htaccess funcione
    && a2enmod rewrite

# Copiar configuración personalizada de Apache
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
