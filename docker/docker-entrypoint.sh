#!/bin/sh
set -e

wait_for_database() {
    echo "[entrypoint] Waiting for database..."
    attempt=0
    until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; do
        attempt=$((attempt + 1))
        if [ "$attempt" -ge 60 ]; then
            echo "[entrypoint] ERROR: database not ready after ${attempt} attempts" >&2
            return 1
        fi
        sleep 1
    done
    echo "[entrypoint] Database is ready."
}

case "$1" in
    healthcheck)
        # /ready performs the real dependency pings (DB, Redis) — ADR-0005.
        curl -fsS "http://localhost/ready" >/dev/null || exit 1
        exit 0
        ;;
esac

# The Caddy mercure block refuses an empty key even when real-time features
# are unused; fall back to a random per-boot key so the image always starts.
if [ -z "$MERCURE_JWT_SECRET" ]; then
    MERCURE_JWT_SECRET="$(head -c 32 /dev/urandom | base64)"
    export MERCURE_JWT_SECRET
fi

if [ "$APP_ENV" = 'prod' ]; then
    wait_for_database
    echo "[entrypoint] Running migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    echo "[entrypoint] Warming up cache..."
    php bin/console cache:warmup
fi

echo "[entrypoint] Starting FrankenPHP..."
exec docker-php-entrypoint "$@"
