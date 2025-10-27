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
    shadow tzdata openssl mysql-client build-base
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl mbstring zip bcmath gd
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
# 4) Final runtime image
############################
FROM php-base AS runtime
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . ./
COPY --from=node-build /app/public/build ./public/build
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} appgroup || true \
    && adduser -D -G appgroup -u ${PUID} appuser || true \
    && chown -R appuser:appgroup storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache
USER appuser
EXPOSE 9000
CMD ["php-fpm"]

############################
# 5) Dev image (Hot reload)
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

# Nota: No instalamos Node dentro del contenedor PHP en modo dev
# porque ya existe un servicio separado 'vite' (node:${NODE_VERSION}-alpine)
# que monta el mismo volumen y ejecuta npm install + npm run dev.
# Si en el futuro quieres tener Node aquí (por ejemplo para ejecutar artisan vite:...)
# puedes copiar el binario desde la imagen oficial:
#   COPY --from=node:${NODE_VERSION}-alpine /usr/local/bin/node /usr/local/bin/
#   COPY --from=node:${NODE_VERSION}-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
#   RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
USER root
EXPOSE 9000
CMD ["php-fpm"]
