FROM php:8.6.0-fpm-alpine3.24 AS php_base

WORKDIR /app

EXPOSE 9000

## < Install PHP extensions
ENV XDEBUG_MODE=off

RUN docker-php-ext-enable opcache 2>/dev/null || true

# PHP 8.6 images no longer ship pecl, and the released redis/apcu do not build on 8.6 yet:
# build them from pinned upstream commits. Back to pecl once releases support PHP 8.6.
ARG PHPREDIS_REF=a6ce1e60b3e81ce3b25174aa5aaefd5590dc64fd
ARG APCU_REF=66300e28647c
# hadolint ignore=DL3003,DL3018,SC2086
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS git \
	&& git clone -q https://github.com/phpredis/phpredis.git /tmp/phpredis \
	&& git -C /tmp/phpredis checkout -q "$PHPREDIS_REF" \
	&& (cd /tmp/phpredis && phpize && ./configure && make -j"$(nproc)" && make install) \
	&& git clone -q https://github.com/krakjoe/apcu.git /tmp/apcu \
	&& git -C /tmp/apcu checkout -q "$APCU_REF" \
	&& (cd /tmp/apcu && phpize && ./configure && make -j"$(nproc)" && make install) \
	&& docker-php-ext-enable redis apcu \
	&& rm -rf /tmp/phpredis /tmp/apcu \
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

# mbstring is no longer bundled in the PHP 8.6 images
# hadolint ignore=DL3018
RUN apk add --no-cache \
	oniguruma-dev \
	&& docker-php-ext-install mbstring

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

COPY --link .docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/20-app.dev.ini

# No pecl on PHP 8.6 images: build xdebug from its first release supporting 8.6.
ARG XDEBUG_REF=3.6.0alpha1
# hadolint ignore=DL3003,DL3018,SC2086
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers git \
	&& git clone -q https://github.com/xdebug/xdebug.git /tmp/xdebug \
	&& git -C /tmp/xdebug checkout -q "$XDEBUG_REF" \
	&& (cd /tmp/xdebug && phpize && ./configure --enable-xdebug && make -j"$(nproc)" && make install) \
	&& docker-php-ext-enable xdebug \
	&& rm -rf /tmp/xdebug \
	&& apk del .build-deps

COPY --link \
	--from=ghcr.io/symfony-cli/symfony-cli:5.23.0 \
	/usr/local/bin/symfony /usr/local/bin/symfony

## < Panther / Selenium
ENV PANTHER_NO_REDUCED_MOTION=1
ENV PANTHER_ERROR_SCREENSHOT_DIR=/app/var/screenshots
ENV PANTHER_EXTERNAL_BASE_URI=http://web:8080
## > Panther / Selenium

ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app && adduser -u ${UID} -G app -s /bin/sh -D app

USER ${UID}:${GID}

FROM php_base AS php_prod

ENV APP_ENV=prod
ENV XDEBUG_MODE=off

COPY --link .docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/20-app.prod.ini

## < Composer stuff
COPY composer.* symfony.* ./

RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY . ./

# .env.dev is consumed here to populate %env(...)% processors in config/services.yaml at build time.
# Keep .env.dev in sync with all keys referenced there; missing keys break the prod release build.
RUN mv .env.dev .env && chown www-data:www-data .env

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

## < Sync /app/public into the volume shared with nginx on every start
## (see docker-entrypoint-prod.sh for why this can't just rely on the
## COPY above: /app/public is overlaid by a named Docker volume at runtime).
# hadolint ignore=DL3018
RUN apk add --no-cache rsync \
	&& mv public public-src
COPY --link .docker/php/docker-entrypoint-prod.sh /usr/local/bin/docker-entrypoint-prod.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-prod.sh

ENTRYPOINT ["docker-entrypoint-prod.sh"]
## Note: setting ENTRYPOINT resets any CMD inherited from the base image, so
## it must be redeclared explicitly to keep starting php-fpm by default.
CMD ["php-fpm"]
## > Sync /app/public into the volume shared with nginx on every start
