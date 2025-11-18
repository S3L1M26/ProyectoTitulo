## Multi-stage Dockerfile for Laravel + Vite (React/Inertia)
## Usage (production build):
##   docker build -t webrtcapp .

ARG PHP_VERSION=8.4.3
ARG NODE_VERSION=20

############################
# 1) Base PHP image
############################
FROM php:${PHP_VERSION}-fpm-alpine AS php-base
ENV TZ=UTC \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1
RUN apk add --no-cache \
    bash git curl zip unzip icu-dev oniguruma-dev libzip-dev libpng-dev \
        shadow tzdata openssl mysql-client build-base linux-headers \
    tesseract-ocr tesseract-ocr-data-spa poppler-utils imagemagick
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl mbstring zip bcmath gd sockets pcntl opcache
# Copiar configuración PHP-FPM optimizada
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
# Copiar configuración de OPcache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
# Instalar extensión Redis para PHP
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS
ARG INSTALL_XDEBUG=0
RUN if [ "$INSTALL_XDEBUG" = "1" ]; then \
      apk add --no-cache $PHPIZE_DEPS && pecl install xdebug && docker-php-ext-enable xdebug ; \
    fi
WORKDIR /var/www/html

############################
# 2) Composer dependencies layer
############################
FROM php-base AS vendor
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --no-scripts --prefer-dist --no-progress --no-interaction

############################
# 3) Node build stage
############################
FROM node:${NODE_VERSION}-alpine AS node-build
WORKDIR /app

# Copiamos manifests (asumimos npm; si usas yarn agrega yarn.lock explícitamente)
COPY package.json package-lock.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

# Copiamos sólo lo necesario para build de assets
COPY resources/ resources/
COPY vite.config.js .
COPY tailwind.config.js .
COPY postcss.config.js .

RUN npm run build

############################
# 4) Dev image (Hot reload - para desarrollo local)
############################
FROM php-base AS dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
# Instalamos dependencias sin ejecutar scripts (porque artisan aún no existe)
RUN composer install --no-scripts --no-interaction --prefer-dist

# Ahora copiamos el resto del código fuente (incluye artisan y bootstrap/)
COPY . ./

# Ejecutamos scripts diferidos (package:discover, etc.)
RUN composer run-script post-autoload-dump || true

USER root
EXPOSE 9000
CMD ["php-fpm"]

############################
# 5) Final runtime image - PRODUCCIÓN (último stage = default)
############################
FROM php-base AS runtime

# Instalar RoadRunner
RUN curl -L https://github.com/roadrunner-server/roadrunner/releases/download/v2024.1.0/roadrunner-2024.1.0-linux-amd64.tar.gz -o rr.tar.gz \
    && tar -xzf rr.tar.gz \
    && mv roadrunner-2024.1.0-linux-amd64/rr /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr \
    && rm -rf rr.tar.gz roadrunner-2024.1.0-linux-amd64

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . ./
COPY --from=node-build /app/public/build ./public/build

ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} appgroup || true \
    && adduser -D -G appgroup -u ${PUID} appuser || true \
    && chown -R appuser:appgroup /var/www/html \
    && chmod -R ug+rw /var/www/html

USER appuser

EXPOSE 8000

# Usar Octane con RoadRunner
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000", "--workers=4", "--max-requests=500"]

