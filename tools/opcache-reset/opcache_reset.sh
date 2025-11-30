#!/bin/bash

php -r "opcache_reset();"

WEBDIR=/var/www/html/public
RANDOM_NAME=$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 13)

echo "<?php opcache_reset(); ?>" >${WEBDIR}${RANDOM_NAME}.php

curl http://localhost:9000/${RANDOM_NAME}.php

rm ${WEBDIR}${RANDOM_NAME}.php
