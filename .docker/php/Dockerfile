FROM php:8.5.5-fpm-alpine3.23 AS php_base

WORKDIR /app

EXPOSE 9000

COPY ./.docker/php/conf.d /usr/local/etc/php/conf.d

## < Install PHP extensions
ENV XDEBUG_MODE=off

RUN docker-php-ext-enable opcache 2>/dev/null || true

# hadolint ignore=DL3018,SC2086
RUN apk add --no-cache $PHPIZE_DEPS \
	&& pecl install redis \
	&& docker-php-ext-enable redis \
	&& apk del $PHPIZE_DEPS

	# hadolint ignore=DL3018,SC2086
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
	&& pecl install apcu \
	&& docker-php-ext-enable apcu \
	&& apk del .build-deps

# hadolint ignore=DL3018
RUN apk add --no-cache \
	libzip-dev \
	zlib-dev \
	&& docker-php-ext-install zip

# hadolint ignore=DL3018
RUN apk add --no-cache \
	icu-dev \
	&& docker-php-ext-install intl

# hadolint ignore=DL3018,DL4006,SC2015
RUN php -m | grep -qi iconv \
	&& docker-php-ext-install ctype \
	|| (apk add --no-cache libiconv-dev \
	&& docker-php-ext-configure iconv --with-iconv=/usr/local \
	&& docker-php-ext-install ctype iconv)
## > Install PHP extensions

## < Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
	php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
	php -r "unlink('composer-setup.php');"

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
## > Install composer

COPY --link .docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/10-app.ini

FROM php_base AS php_dev

ENV APP_ENV=dev

# hadolint ignore=DL3018,SC2086
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
	&& pecl install xdebug \
	&& docker-php-ext-enable xdebug \
	&& apk del .build-deps

COPY --link \
	--from=ghcr.io/symfony-cli/symfony-cli:5.17.1 \
	/usr/local/bin/symfony /usr/local/bin/symfony

## < Install chromedriver
ENV PANTHER_NO_SANDBOX=1
ENV PANTHER_NO_REDUCED_MOTION=1
ENV PANTHER_ERROR_SCREENSHOT_DIR=/app/var/screenshots
ENV PANTHER_DEVTOOLS=disabled
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
ENV PANTHER_CHROME_DRIVER_BINARY=/usr/local/bin/chromedriver
ENV PANTHER_CHROME_BINARY=/usr/local/bin/chrome
ENV GNUPGHOME=/usr/local/share/gnupg

# hadolint ignore=DL3018
RUN apk add --no-cache \
	gnupg \
	unzip \
	wget \
	chromium \
	chromium-chromedriver \
	fontconfig \
	freetype \
	libx11 \
	libxext \
	libxrender \
	libxtst

RUN ln -sf /usr/bin/chromium-browser /usr/local/bin/chrome \
	&& ln -sf /usr/bin/chromedriver /usr/local/bin/chromedriver
## > Install chromedriver

FROM php_base AS php_prod

ENV APP_ENV=prod
ENV XDEBUG_MODE=off

COPY --link .docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/20-app.prod.ini

## < Composer stuff
COPY composer.* symfony.* ./

RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY . ./

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

RUN chown -R www-data:www-data var/
