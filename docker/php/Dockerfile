FROM php:8.4-fpm AS php_base

WORKDIR /var/www/html

EXPOSE 9000

COPY ./docker/php/conf.d /usr/local/etc/php/conf.d

## < Install PHP extensions
ENV XDEBUG_MODE=off

RUN docker-php-ext-install opcache

RUN pecl install redis \
	&& docker-php-ext-enable redis

# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libmemcached-dev \
        libssl-dev \
        zlib1g-dev \
	&& pecl install memcached-3.2.0 \
	&& docker-php-ext-enable memcached \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install apcu \
    && docker-php-ext-enable apcu

# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev\ 
        zlib1g-dev \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# hadolint ignore=DL3008
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install ctype iconv
## > Install PHP extensions

## < Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
## > Install composer

FROM php_base AS php_dev

ENV APP_ENV=dev

RUN pecl install xdebug \
	&& docker-php-ext-enable xdebug

## < Install chromedriver
ENV PANTHER_NO_SANDBOX=1
ENV PANTHER_NO_REDUCED_MOTION=1
ENV PANTHER_ERROR_SCREENSHOT_DIR=/var/www/html/var/screenshots
ENV PANTHER_DEVTOOLS=disabled
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
ENV GNUPGHOME=/usr/local/share/gnupg

# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
        gpg \
        unzip \
        wget \
        chromium \ 
        chromium-driver \ 
        libgconf-2-4 \
        libfontconfig-dev \
        libfreetype6 \
        libx11-6 \
        libxext6 \
        libxrender1 \
        libxtst6 \
        dirmngr \
        gpg-agent \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p $GNUPGHOME \
    && chmod 700 $GNUPGHOME \
    && wget -q -nv -O phive.phar https://phar.io/releases/phive.phar \
    && wget -q -nv -O phive.phar.asc https://phar.io/releases/phive.phar.asc \    
    && gpg --refresh-keys \
    && gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys 0x9D8A98B29B2D5D79 \
    && gpg --verify phive.phar.asc phive.phar \
    && chmod +x phive.phar \
    && mv phive.phar /usr/local/bin/phive

ENV DEBIAN_FRONTEND=noninteractive

SHELL ["/bin/bash", "-o", "pipefail", "-c"]
RUN (echo "y" | /usr/local/bin/phive install --global bdi) \
    && bdi detect /usr/local/bin/

## > Install chromedriver

FROM php_base AS php_prod

USER www-data

ENV APP_ENV=prod
ENV XDEBUG_MODE=off

## < Composer stuff
COPY --chown=www-data composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --chown=www-data . ./
RUN mv .env.prod .env && chown www-data:www-data .env

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer run-script --no-dev post-install-cmd; \
	sync;
## > Composer stuff

## < Cleanup
RUN rm .env \
    && touch .env
## > Cleanup
