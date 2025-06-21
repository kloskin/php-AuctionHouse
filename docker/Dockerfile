FROM php:8.3-apache

# Instalacja rozszerzeń MongoDB i Redis
RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

# Włącz mod_rewrite dla Apache
RUN a2enmod rewrite

# Kopiuj własne php.ini
COPY docker/php.ini /usr/local/etc/php/

# Ustaw katalog roboczy
WORKDIR /var/www/html
