FROM php:8.3-apache

# Instalacja rozszerzeń MongoDB i Redis
RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

# Kopiuj własne php.ini
COPY docker/php.ini /usr/local/etc/php/

# Ustaw katalog roboczy
WORKDIR /var/www/html

COPY docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

# Włącz mod_rewrite dla Apache
RUN a2enmod rewrite
