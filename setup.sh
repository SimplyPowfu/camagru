#!/bin/sh
set -e

# Attende che il database sia raggiungibile sulla porta 3306
echo "Waiting for database to be ready..."
until nc -z -v -w30 $DB_HOST 3306; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is up - executing setup"

php config/setup.php

exec php-fpm