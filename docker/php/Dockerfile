FROM php:8.2-fpm

## < Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"
## > Install composer

## < Install PHP extensions
ENV XDEBUG_MODE off

RUN docker-php-ext-install opcache

RUN pecl install redis \
	&& pecl install xdebug \
	&& docker-php-ext-enable redis xdebug

RUN apt-get update \
    && apt-get install -y \
        libmemcached-dev \
        libssl-dev \
        zlib1g-dev \
	&& pecl install memcached-3.2.0 \
	&& docker-php-ext-enable memcached

RUN apt-get update \
    && apt-get install -y libzip-dev zlib1g-dev \
    && docker-php-ext-install zip

RUN apt-get update \
    && apt-get install -y libicu-dev \
    && docker-php-ext-install intl

RUN pecl install apcu \
    && docker-php-ext-enable apcu

RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*
## > Install PHP extensions

## < Install chromedriver
ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
ENV GNUPGHOME /usr/local/share/gnupg

RUN apt-get update && apt-get install -y \
    gpg \
    unzip \
    wget \
    chromium \ 
    libgconf-2-4 \
    libfontconfig \
    libfreetype6 \
    libx11-6 \
    libxext6 \
    libxrender1 \
    libxtst6

RUN mkdir -p $GNUPGHOME \
    && chmod 700 $GNUPGHOME \
    && wget -O phive.phar https://phar.io/releases/phive.phar \
    && wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc \    
    && gpg --refresh-keys \
    && gpg --keyserver keyserver.ubuntu.com --recv-keys 0x9D8A98B29B2D5D79 \
    && gpg --verify phive.phar.asc phive.phar \
    && chmod +x phive.phar \
    && mv phive.phar /usr/local/bin/phive

ENV DEBIAN_FRONTEND noninteractive

RUN (echo "y" | /usr/local/bin/phive install --global bdi) \
    && bdi detect /usr/local/bin/

RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/*
## > Install chromedriver