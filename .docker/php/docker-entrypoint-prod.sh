#!/bin/sh
set -e

# /app/public is a named Docker volume shared read-only with the nginx
# container. Docker only auto-populates a named volume from the image the
# first time it's created (when empty); on every later deploy the volume
# keeps stale content and new/changed files under public/ never reach it.
# Resync explicitly from the golden copy baked into the image on every start.
rsync -a --delete /app/public-src/ /app/public/

exec docker-php-entrypoint "$@"
