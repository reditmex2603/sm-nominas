#!/bin/sh
set -e

cd /var/www/html

# Ejecuta el scheduler de Laravel cada minuto.
# La primera ejecución es inmediata para no esperar 60s al arrancar.
while true; do
    php artisan schedule:run --no-interaction --quiet
    sleep 60
done
