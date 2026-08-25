# syntax=docker/dockerfile:1

# ==============================================================================
# BUILD — Debian (glibc) + PHP 8.4 + Node 22.
# El frontend usa binarios nativos glibc (rollup, tailwind oxide, lightningcss)
# declarados en package.json, por lo que el build NO puede correr en Alpine (musl).
# wayfinder además ejecuta `php artisan wayfinder:generate` durante el build de Vite.
# ==============================================================================
FROM php:8.4-fpm-bookworm AS builder

ARG VITE_APP_NAME="Nomina SM"

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg unzip \
        libicu-dev libzip-dev libonig-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath intl zip pcntl opcache \
    && printf "no\nno\nno\nno\nno\n" | pecl install redis \
    && docker-php-ext-enable redis \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get remove -y $PHPIZE_DEPS libicu-dev libzip-dev libonig-dev curl gnupg \
    && rm -rf /var/lib/apt/lists/* /tmp/pear ~/.pearrc

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencias de PHP
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Dependencias de Node (respetando el ignore-scripts=true del proyecto)
COPY .npmrc ./
COPY package.json package-lock.json ./
RUN npm ci

# Código fuente de la aplicación
COPY . .

# Directorios que wayfinder/Blade necesitan durante el build (storage no viaja en el build context)
RUN mkdir -p \
        storage/framework/views \
        storage/framework/cache \
        storage/framework/sessions \
        storage/logs \
        bootstrap/cache

# Manifiesto de paquetes + build de cliente y SSR, y limpieza de node_modules.
# APP_KEY/APP_ENV son dummy solo para que artisan pueda bootear durante el build.
RUN export APP_ENV=production APP_DEBUG=false \
        APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" \
        VITE_APP_NAME="$VITE_APP_NAME" \
    && php artisan package:discover --ansi \
    && npm run build:ssr \
    && rm -rf node_modules

# ==============================================================================
# PHP — imagen final de la aplicación (PHP-FPM, sin Node)
# ==============================================================================
FROM php:8.4-fpm-bookworm AS php

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        libicu-dev libzip-dev libonig-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath intl zip pcntl opcache \
    && printf "no\nno\nno\nno\nno\n" | pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get remove -y $PHPIZE_DEPS libicu-dev libzip-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/pear ~/.pearrc

# Configuración PHP (opcache + límites)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-php.ini

# Configuración PHP-FPM (pool no-root, pid escribible, clear_env)
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www/html

# Código completo (vendor, public/build, bootstrap/ssr, manifest de paquetes)
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Enlace simbólico de storage (relativo, resuelve en app y web) + directorios
RUN rm -rf public/storage \
    && ln -s ../storage/app/public public/storage \
    && mkdir -p \
        storage/app/public \
        storage/app/documentos \
        storage/app/private \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint y script del scheduler
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/scripts/scheduler.sh /usr/local/bin/scheduler.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/scheduler.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# ==============================================================================
# SSR — Node sirviendo el bundle de Inertia SSR (bootstrap/ssr/app.js, puerto 13714)
# ==============================================================================
FROM node:22-alpine AS ssr

WORKDIR /app

# El build de Vite genera `bootstrap/ssr/app.js` (bundle ESM) + assets/
COPY --from=builder --chown=node:node /var/www/html/bootstrap/ssr ./bootstrap/ssr

# `app.js` y assets/ son ESM; se marca el directorio como módulo para que
# node los interprete correctamente en cualquier versión de Node 22.
RUN printf '{"type":"module"}\n' > bootstrap/ssr/package.json

USER node

EXPOSE 13714

CMD ["node", "bootstrap/ssr/app.js"]

# ==============================================================================
# WEB — Nginx con los assets estáticos de public/ (build de Vite) horneados
# ==============================================================================
FROM nginx:1.27-alpine AS web

COPY --from=php /var/www/html/public /var/www/html/public
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
