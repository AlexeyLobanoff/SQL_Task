FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

# Включаем mod_rewrite (если понадобится)
RUN a2enmod rewrite

COPY php.ini /usr/local/etc/php/php.ini
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
