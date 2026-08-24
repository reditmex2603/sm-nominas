#!/bin/sh
set -e

cd /var/www/html

# 1. Regenerar el manifiesto de paquetes (composer install se ejecutó con --no-scripts)
php artisan package:discover --ansi

# 2. Preparar directorios de storage (por si el volumen se monta vacío)
mkdir -p \
    storage/app/public \
    storage/app/documentos \
    storage/app/private \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

# Enlace simbólico de storage (idempotente; normalmente ya viene en la imagen)
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# 3. Migraciones de base de datos con reintento (la BD puede tardar en aceptar conexiones)
attempt=1
until php artisan migrate --force; do
    attempt=$((attempt + 1))
    if [ "$attempt" -gt 10 ]; then
        echo "ERROR: no se pudo migrar la base de datos tras $attempt intentos." >&2
        exit 1
    fi
    echo "Base de datos no disponible, reintentando ($attempt/10) en 5s..."
    sleep 5
done

# 4. Optimización de caches en producción (config, rutas, vistas y eventos)
php artisan optimize

# 5. Arrancar PHP-FPM en primer plano
exec "$@"
