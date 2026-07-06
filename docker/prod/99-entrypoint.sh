#!/usr/bin/env bash
set -e

ROLE="${CONTAINER_ROLE:-app}"
READY_FLAG=/var/www/html/storage/.app-ready

echo "🚀 checkstu entrypoint (role=${ROLE})"

if [ "${ROLE}" = "app" ]; then
    # Clear any stale readiness flag from a previous run.
    rm -f "${READY_FLAG}"

    # Storage skeleton (the storage/ volume starts empty on first boot).
    mkdir -p \
        /var/www/html/storage/logs \
        /var/www/html/storage/framework/cache \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/app/public \
        /var/www/html/storage/database \
        /var/www/html/storage/backups \
        /var/www/html/bootstrap/cache

    # Ensure the SQLite database file exists.
    DB_FILE="${DB_DATABASE:-/var/www/html/storage/database/database.sqlite}"
    mkdir -p "$(dirname "${DB_FILE}")"
    [ -f "${DB_FILE}" ] || touch "${DB_FILE}"

    echo "🔄 Running migrations..."
    php artisan migrate --force

    if [ "${RUN_SEEDER:-false}" = "true" ]; then
        echo "🌱 Seeding database..."
        php artisan db:seed --force || true
    fi

    echo "⚡ Optimizing (config/route/view cache)..."
    php artisan optimize

    # Signal readiness for the worker/scheduler containers.
    touch "${READY_FLAG}"
    echo "✅ App initialization complete."

elif [ "${ROLE}" = "worker" ] || [ "${ROLE}" = "scheduler" ]; then
    echo "⏳ [${ROLE}] Waiting for the app container to finish migrating..."
    elapsed=0
    until [ -f "${READY_FLAG}" ]; do
        if [ "${elapsed}" -ge 120 ]; then
            echo "❌ [${ROLE}] App not ready after 120s. Aborting."
            exit 1
        fi
        sleep 1
        elapsed=$((elapsed + 1))
    done
    echo "✅ [${ROLE}] App is ready."

else
    echo "❌ Unknown CONTAINER_ROLE: ${ROLE}"
    exit 1
fi
