# syntax=docker/dockerfile:1

# ---------- PHP build stage (composer deps + app) ----------
FROM serversideup/php:8.5-fpm-nginx AS php-build
WORKDIR /var/www/html

USER root
RUN install-php-extensions intl pdo_sqlite gmp

COPY . .

ENV APP_ENV=build

RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader
RUN composer dump-autoload -o

# ---------- Asset build stage (Node for Vite build only) ----------
FROM node:22-bookworm-slim AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

# Copy the full front-end source (node_modules/vendor excluded via .dockerignore)
# so the `@/*` alias and tsconfig resolve exactly as they do locally.
COPY . .
COPY --from=php-build /var/www/html/vendor ./vendor

# Client build only (no SSR server in production).
RUN npm run build

# ---------- Runtime image (no Node, no dev tools) ----------
FROM serversideup/php:8.5-fpm-nginx AS runtime
WORKDIR /var/www/html
USER root

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

LABEL org.opencontainers.image.title="checkstu" \
      org.opencontainers.image.description="Household task management app" \
      org.opencontainers.image.authors="Dominik Warch <dominik.warch@geoventis.de>" \
      org.opencontainers.image.base.name="serversideup/php:8.5-fpm-nginx" \
      org.opencontainers.image.version="${APP_VERSION}"

# Security upgrades (fresh package index, no stale cache).
RUN apt-get update && \
    apt-get -y upgrade && \
    apt-get install -y --no-install-recommends ca-certificates sqlite3 && \
    apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/*

# gmp: minishlink/web-push (VAPID push encryption) errors out without GMP or
# BCMath — Laravel promotes its trigger_error() notice to a fatal exception.
RUN install-php-extensions intl pdo_sqlite gmp

# nginx tweaks
COPY docker/prod/nginx.conf /etc/nginx/conf.d/custom.conf
COPY docker/prod/server-opts-sw.conf /etc/nginx/server-opts.d/sw.conf

# App (incl. vendor) from php-build
COPY --from=php-build --chown=www-data:www-data /var/www/html .

# Drop any dev leftovers that slipped through the context
RUN rm -f .env public/hot

ENV APP_ENV=build

# Built front-end assets only (no node_modules)
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Env-independent caches at build time; config:cache/optimize happen at runtime.
RUN php artisan storage:link && \
    php artisan view:cache && \
    php artisan route:cache

# Entrypoint (migrate/optimize for the app role; wait for others)
COPY --chmod=755 docker/prod/99-entrypoint.sh /etc/entrypoint.d/99-entrypoint.sh

RUN mkdir -p /var/www/html/storage/database && \
    chown -R www-data:www-data /var/www/html/storage

USER www-data
