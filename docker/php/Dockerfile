FROM php:8.2-fpm-alpine AS php-converter

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#ssh key is needed for downloading vendors
RUN apk add git openssh
RUN mkdir ~/.ssh

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/php-converter
COPY ../../. ./

ENV COMPOSER_ALLOW_SUPERUSER=1
