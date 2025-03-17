# Stage 1: Build stage
FROM php:8.2-fpm AS builder

RUN apt-get update && apt-get upgrade -y

RUN apt install nginx=1.* -y

RUN apt-get install -y --no-install-recommends libxml2-dev libxslt-dev \
    && docker-php-ext-install xsl

RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip

RUN apt-get install -y --no-install-recommends libpng-dev \
    && docker-php-ext-install gd


RUN docker-php-ext-install pdo_mysql 

RUN apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html


WORKDIR /var/www/html