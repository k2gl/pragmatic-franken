#!/usr/bin/env bash
# Deploy one environment (stage or prod) on the VDS.
#
# Run from the environment's git checkout (cwd = repo root), e.g.
#   cd /srv/app/prod && ENVIRONMENT=prod REF=v1.2.3 ./ops/deploy.sh
#   cd /srv/app/stage && ENVIRONMENT=stage REF=origin/main ./ops/deploy.sh
#
# Builds the php_prod image, then swaps the app container with ZERO DOWNTIME
# via ops/rollout.sh (blue-green: new replica up alongside the old, wait for
# /ready, drop the old). The container entrypoint runs migrations and cache
# warmup on boot.
set -euo pipefail

ENVIRONMENT="${ENVIRONMENT:-${1:-}}"
REF="${REF:-${2:-}}"

[ -n "$ENVIRONMENT" ] || { echo "[deploy] ENVIRONMENT (stage|prod) is required"; exit 1; }

ENV_FILE=".env.${ENVIRONMENT}"
[ -f "$ENV_FILE" ] || { echo "[deploy] $ENV_FILE not found in $(pwd)"; exit 1; }

COMPOSE=(docker compose -f docker/compose.prod.yml --env-file "$ENV_FILE")

echo "[deploy] Environment: $ENVIRONMENT  Ref: ${REF:-<current>}"

# Code update + re-exec. bash reads the script as it executes, so a
# `git reset --hard` that changes deploy.sh itself mid-run would execute a mix
# of old and new versions. After checking out the requested ref we re-exec the
# already-updated deploy.sh (DEPLOY_REEXEC suppresses a second fetch) — script
# changes take effect within the same deploy, without corrupting execution.
if [ -z "${DEPLOY_REEXEC:-}" ]; then
    echo "[deploy] Fetching code..."
    git fetch --all --tags --prune
    if [ -n "$REF" ]; then
        # A bare branch name (e.g. REF=main) must resolve to the freshly
        # fetched REMOTE branch, not the server's stale local branch — `git
        # reset --hard main` resets to local `main`, which `git fetch` never
        # advances, so the deploy would silently ship old code. Prefer
        # origin/<ref> when it resolves; fall back to <ref> as-is for tags,
        # SHAs and origin/<branch> forms.
        if git rev-parse --verify --quiet "origin/${REF}^{commit}" >/dev/null; then
            target="origin/${REF}"
        else
            target="$REF"
        fi
        echo "[deploy] Resetting to ${target}"
        git reset --hard "$target"
    fi
    export DEPLOY_REEXEC=1
    exec "$0" "$@"
fi
git log -1 --oneline

echo "[deploy] Building image..."
# --pull refreshes the base images (frankenphp/composer) each build so
# upstream security patches land without manual intervention; the deploy is
# already vetted on stage before a manual prod dispatch.
"${COMPOSE[@]}" build --pull

# Pre-migration safety dump (prod only). The freshly-built container migrates
# the LIVE prod DB on boot, with no built-in revert — so take a verified dump
# first. Once the backup dir is writable, the dump GATES the deploy (set -e
# aborts if pg_dump fails). If the dir isn't set up yet, warn and continue —
# a missing one-time setup must not brick deploys. First deploy: no db yet.
if [ "$ENVIRONMENT" = "prod" ]; then
    if [ -z "$("${COMPOSE[@]}" ps -q db 2>/dev/null)" ]; then
        echo "[deploy] No running db container yet — skipping pre-migration dump (first deploy)."
    else
        BACKUP_DIR="${BACKUP_DIR:-/srv/app/backups/$ENVIRONMENT}"
        if mkdir -p "$BACKUP_DIR" 2>/dev/null && [ -w "$BACKUP_DIR" ]; then
            DUMP_FILE="$BACKUP_DIR/predeploy-$(date +%F_%H%M%S).dump"
            echo "[deploy] Dumping prod DB before migration -> $DUMP_FILE"
            "${COMPOSE[@]}" exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -Fc "$POSTGRES_DB"' > "$DUMP_FILE"
            echo "[deploy] Pre-migration dump OK ($(du -h "$DUMP_FILE" | cut -f1))."
        else
            echo "[deploy] WARNING: '$BACKUP_DIR' not writable — skipping pre-migration dump." >&2
            echo "[deploy]   One-time setup (as the deploy user): mkdir -p $BACKUP_DIR" >&2
        fi
    fi
fi

# Infra and the background worker go up the normal way (idempotent; db/redis
# are not recreated unless their image changed, a short worker pause is not
# user-visible). --remove-orphans cleans containers of services removed from
# the compose file (within THIS project; other envs/proxy are untouched).
echo "[deploy] Bringing up db/redis/worker..."
"${COMPOSE[@]}" up -d --no-deps --remove-orphans db redis
"${COMPOSE[@]}" up -d --no-deps worker

# Zero-downtime swap of the user-facing app: a new container comes up next to
# the old one, we wait for its /ready, then drop the old. The front Caddy
# resolves the alias dynamically and fails over — no window without a live
# upstream. NB: during the overlap the old code briefly runs on the ALREADY
# migrated schema → migrations must be backward-compatible (expand/contract:
# add nullable/with-default; rename/drop in two phases across two releases).
echo "[deploy] Zero-downtime rollout of app..."
COMPOSE="${COMPOSE[*]}" ./ops/rollout.sh app

echo "[deploy] Pruning dangling images..."
docker image prune -f > /dev/null 2>&1 || true

echo "[deploy] Status:"
"${COMPOSE[@]}" ps
echo "[deploy] ✅ Done."
