#!/bin/bash

APP_DIR=$(pwd)
APP_OWNER="www-data"
COMPOSER=$(which composer)
CHOWN=$(which chown)

cd "$APP_DIR" && $COMPOSER install
php "$APP_DIR/bin/console" doctrine:generate:entities Elastic --no-backup
php "$APP_DIR/bin/console" doctrine:schema:update --force
php "$APP_DIR/bin/console" doctrine:cache:clear-result
php "$APP_DIR/bin/console" doctrine:cache:clear-query
php "$APP_DIR/bin/console" doctrine:cache:clear-metadata
php "$APP_DIR/bin/console" cache:clear --env=dev --no-warmup
php "$APP_DIR/bin/console" assets:install web --symlink
#cd "$APP_DIR" && $COMPOSER dump-autoload --optimize
$CHOWN -R "$APP_OWNER":"$APP_OWNER" "$APP_DIR"