# To copy composer binary file
FROM composer:1.8.5 as composer

################################################################################

FROM php:7.1-alpine as php7.1

RUN apk add --no-cache \
        autoconf \
        g++\
        git \
        libssh2-dev \
        make \
        zlib-dev \
    && pecl install \
        mongodb \
        ssh2-1.1.2

RUN docker-php-ext-install \
        zip \
    && docker-php-ext-enable \
        mongodb \
        ssh2

COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME /var/cache/composer
RUN mkdir /var/cache/composer \
    && chown 1000:1000 /var/cache/composer

USER 1000

RUN composer global require "hirak/prestissimo" --prefer-dist

WORKDIR /app

################################################################################

FROM php:7.2-alpine as php7.2

RUN apk add --no-cache \
        autoconf \
        g++\
        git \
        libssh2-dev \
        make \
        zlib-dev \
    && pecl install \
        mongodb \
        ssh2-1.1.2

RUN docker-php-ext-install \
        zip \
    && docker-php-ext-enable \
        mongodb \
        ssh2

COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME /var/cache/composer
RUN mkdir /var/cache/composer \
    && chown 1000:1000 /var/cache/composer

USER 1000

RUN composer global require "hirak/prestissimo" --prefer-dist

WORKDIR /app

################################################################################

FROM php:7.3-alpine as php7.3

RUN apk add --no-cache \
        autoconf \
        g++\
        git \
        # libssh2-dev \
        libzip-dev \
        make \
        zlib-dev \
    && pecl install \
        mongodb
        # ssh2-1.1.2
        # ssh2 extension is not availble yet for php7.3
        # see https://serverpilot.io/docs/how-to-install-the-php-ssh2-extension

RUN docker-php-ext-install \
        zip \
    && docker-php-ext-enable \
        mongodb
        # ssh2

COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME /var/cache/composer
RUN mkdir /var/cache/composer \
    && chown 1000:1000 /var/cache/composer

USER 1000

RUN composer global require "hirak/prestissimo" --prefer-dist

WORKDIR /app
