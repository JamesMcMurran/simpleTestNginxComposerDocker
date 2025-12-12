#!/bin/sh
set -e

# Install PostgreSQL extensions if not already installed
if ! php -m | grep -q pdo_pgsql; then
    echo "Installing PostgreSQL extensions..."
    apk add --no-cache postgresql-dev
    docker-php-ext-install pdo pdo_pgsql
fi

# Start PHP-FPM
exec php-fpm
