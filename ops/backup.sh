#!/usr/bin/env bash
# Operable backup tool for one environment on the VDS — create / list / restore.
#
#   ENVIRONMENT=prod ./ops/backup.sh                 # = create (cron-compatible)
#   ENVIRONMENT=prod ./ops/backup.sh create
#   ENVIRONMENT=prod ./ops/backup.sh list
#   ENVIRONMENT=prod ./ops/backup.sh restore <name|latest> [--with-secrets] [--yes]
#
# Run from the environment's checkout (/srv/app/<env>) so the relative compose
# path and .env file resolve. ENVIRONMENT comes from the env var (not a
# positional) — the first positional is the subcommand.
#
# A backup is a single timestamped archive `app-<env>-<ts>.tar.gz` containing:
#   * db.dump      — pg_dump -Fc of the running db container
#   * .env         — the environment's secrets (.env.<env>)
#   * manifest.txt — env / timestamp / git SHA / pg version / contents
# Redis is an ephemeral cache (not included).
#
# Archives hold secrets: BACKUP_DIR is chmod 700, archives 600. Off-site copies
# are encrypted (rclone `crypt` remote) — a dump on the same disk is not a backup.
#
# Activation (owner):
#   * cron, every 6 h, as the deploy user (offsite + explicit rclone path,
#     since cron's PATH is minimal):
#       0 */6 * * * cd /srv/app/prod && ENVIRONMENT=prod BACKUP_DIR=/srv/app/backups/prod \
#         RCLONE_REMOTE=crypt: RCLONE_BIN=/home/deploy/bin/rclone \
#         /srv/app/prod/ops/backup.sh >> /srv/app/backups/backup.log 2>&1
#   * configure the encrypted remote with `rclone config` (creds = owner).
#   * monthly restore test into a throwaway DB — an untested backup is not a backup.
#
# Env knobs: BACKUP_DIR, RETENTION_DAYS (local, 14), OFFSITE_RETENTION_DAYS (30),
#            RCLONE_REMOTE (offsite, e.g. `crypt:`), RCLONE_BIN (rclone path).
set -euo pipefail

ENVIRONMENT="${ENVIRONMENT:-}"
[ -n "$ENVIRONMENT" ] || { echo "[backup] ENVIRONMENT (stage|prod) is required" >&2; exit 1; }

ENV_FILE=".env.${ENVIRONMENT}"
[ -f "$ENV_FILE" ] || { echo "[backup] $ENV_FILE not found in $(pwd)" >&2; exit 1; }

COMPOSE=(docker compose -f docker/compose.prod.yml --env-file "$ENV_FILE")

BACKUP_DIR="${BACKUP_DIR:-/srv/app/backups/${ENVIRONMENT}}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
OFFSITE_RETENTION_DAYS="${OFFSITE_RETENTION_DAYS:-30}"
RCLONE_REMOTE="${RCLONE_REMOTE:-}"
RCLONE_BIN="${RCLONE_BIN:-rclone}"

ARCHIVE_GLOB="app-${ENVIRONMENT}-*.tar.gz"

die() { echo "[backup] ERROR: $*" >&2; exit 1; }

pg_dump_live() {
    "${COMPOSE[@]}" exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -Fc "$POSTGRES_DB"'
}

# --- create ----------------------------------------------------------------
cmd_create() {
    mkdir -p "$BACKUP_DIR"
    chmod 700 "$BACKUP_DIR"

    local ts work archive offsite_failed=0
    ts="$(date +%F_%H%M%S)"
    work="$(mktemp -d)"
    # shellcheck disable=SC2064
    trap "rm -rf '$work'" EXIT

    echo "[backup] Dumping ${ENVIRONMENT} DB..."
    pg_dump_live > "$work/db.dump"

    cp "$ENV_FILE" "$work/.env"

    {
        echo "env=${ENVIRONMENT}"
        echo "created=${ts}"
        echo "git=$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
        echo "pg=$("${COMPOSE[@]}" exec -T db sh -c 'psql -U "$POSTGRES_USER" -tAc "show server_version"' 2>/dev/null | tr -d '\r' || echo unknown)"
        echo "contents=db.dump .env"
    } > "$work/manifest.txt"

    archive="${BACKUP_DIR}/app-${ENVIRONMENT}-${ts}.tar.gz"
    tar -czf "$archive" -C "$work" .
    chmod 600 "$archive"
    echo "[backup] Archive OK ($(du -h "$archive" | cut -f1)) -> $archive"

    # Off-site copy — only when configured. A dump on the same disk is not a backup.
    if [ -n "$RCLONE_REMOTE" ]; then
        if ! command -v "$RCLONE_BIN" >/dev/null 2>&1; then
            echo "[backup] ERROR: RCLONE_REMOTE set but '$RCLONE_BIN' not found — offsite SKIPPED" >&2
            offsite_failed=1
        else
            echo "[backup] Copying offsite -> ${RCLONE_REMOTE}"
            if "$RCLONE_BIN" copy "$BACKUP_DIR" "$RCLONE_REMOTE" --include "/${ARCHIVE_GLOB}" \
               && "$RCLONE_BIN" delete --min-age "${OFFSITE_RETENTION_DAYS}d" "$RCLONE_REMOTE"; then
                echo "[backup] Offsite OK."
            else
                echo "[backup] ERROR: offsite copy/prune failed" >&2
                offsite_failed=1
            fi
        fi
    fi

    echo "[backup] Pruning local archives older than ${RETENTION_DAYS} days..."
    find "$BACKUP_DIR" -name "$ARCHIVE_GLOB" -mtime "+${RETENTION_DAYS}" -delete

    echo "[backup] ✅ Done. Recent local archives:"
    ls -1t "$BACKUP_DIR"/${ARCHIVE_GLOB} 2>/dev/null | head -5 || true

    # A broken offsite must not silently degrade to local-only.
    [ "$offsite_failed" -eq 0 ] || die "offsite step failed (see above)"
}

# --- list ------------------------------------------------------------------
cmd_list() {
    declare -A seen
    printf '%-34s %8s  %-12s\n' 'NAME' 'SIZE' 'LOCATION'

    shopt -s nullglob
    local f name size
    for f in "$BACKUP_DIR"/${ARCHIVE_GLOB}; do
        name="$(basename "$f")"
        size="$(du -h "$f" | cut -f1)"
        seen[$name]="local"
        printf '%-34s %8s  %-12s\n' "$name" "$size" "local"
    done
    shopt -u nullglob

    if [ -n "$RCLONE_REMOTE" ] && command -v "$RCLONE_BIN" >/dev/null 2>&1; then
        # rclone lsl: "<bytes> <date> <time> <name>"
        local bytes _date _time rname loc
        while read -r bytes _date _time rname; do
            [ -n "$rname" ] || continue
            loc="offsite"
            [ -n "${seen[$rname]:-}" ] && loc="both"
            printf '%-34s %8s  %-12s\n' "$rname" "$(numfmt --to=iec "$bytes" 2>/dev/null || echo "$bytes")" "$loc"
        done < <("$RCLONE_BIN" lsl "$RCLONE_REMOTE" 2>/dev/null)
    else
        echo "[backup] (offsite not configured — set RCLONE_REMOTE to list remote copies)"
    fi
}

# --- restore ---------------------------------------------------------------
cmd_restore() {
    local target="" with_secrets=0 assume_yes=0
    while [ $# -gt 0 ]; do
        case "$1" in
            --with-secrets) with_secrets=1 ;;
            --yes|-y)       assume_yes=1 ;;
            -*)             die "unknown flag: $1" ;;
            *)              target="$1" ;;
        esac
        shift
    done
    [ -n "$target" ] || die "usage: restore <name|latest> [--with-secrets] [--yes]"

    if [ "$target" = "latest" ]; then
        target="$( { ls -1 "$BACKUP_DIR"/${ARCHIVE_GLOB} 2>/dev/null || true; } | sort | tail -1 | xargs -r basename)"
        if [ -z "$target" ] && [ -n "$RCLONE_REMOTE" ] && command -v "$RCLONE_BIN" >/dev/null 2>&1; then
            target="$("$RCLONE_BIN" lsf "$RCLONE_REMOTE" 2>/dev/null | grep -E "^app-${ENVIRONMENT}-.*\.tar\.gz$" | sort | tail -1)"
        fi
        [ -n "$target" ] || die "no backups found (local or offsite)"
        echo "[backup] latest = $target"
    fi

    local src="${BACKUP_DIR}/${target}"
    if [ ! -f "$src" ]; then
        [ -n "$RCLONE_REMOTE" ] || die "$target not found locally and no offsite configured"
        echo "[backup] Fetching $target from offsite..."
        "$RCLONE_BIN" copy "$RCLONE_REMOTE" "$BACKUP_DIR" --include "/${target}"
        [ -f "$src" ] || die "could not fetch $target from offsite"
    fi

    local stage="${BACKUP_DIR}/restore-${target%.tar.gz}-$(date +%H%M%S)"
    mkdir -p "$stage"; chmod 700 "$stage"
    tar -xzf "$src" -C "$stage"
    [ -f "$stage/db.dump" ] || die "archive $target is missing db.dump"
    echo "[backup] Manifest:"; sed 's/^/    /' "$stage/manifest.txt" 2>/dev/null || true

    if [ "$assume_yes" -ne 1 ]; then
        echo "[backup] This OVERWRITES the live ${ENVIRONMENT} database (pg_restore --clean)."
        read -r -p "[backup] Type the backup name to confirm: " ans
        [ "$ans" = "$target" ] || die "confirmation mismatch — aborted"
    fi

    local safety="${BACKUP_DIR}/pre-restore-$(date +%F_%H%M%S).dump"
    echo "[backup] Safety dump of current DB -> $safety"
    pg_dump_live > "$safety"
    chmod 600 "$safety"

    echo "[backup] Restoring DB from $target..."
    if ! "${COMPOSE[@]}" exec -T db sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner' < "$stage/db.dump"; then
        echo "[backup] WARNING: pg_restore returned non-zero (often benign with --clean/--if-exists)" >&2
    fi

    if [ "$with_secrets" -eq 1 ]; then
        echo "[backup] --with-secrets: applying .env, then restarting the stack."
        cp "$stage/.env" "$ENV_FILE"
        "${COMPOSE[@]}" up -d
        rm -rf "$stage"
        echo "[backup] ✅ Full restore done (DB + secrets)."
    else
        echo "[backup] ✅ DB restored. Safety dump: $safety"
        echo "[backup] Secrets NOT applied (live .env left intact)."
        echo "[backup]   Staged for manual use (DR): $stage  (.env)"
        echo "[backup]   Re-run with --with-secrets to apply them automatically."
    fi
}

# --- dispatch --------------------------------------------------------------
CMD="${1:-create}"
[ $# -gt 0 ] && shift

case "$CMD" in
    create)  cmd_create ;;
    list)    cmd_list ;;
    restore) cmd_restore "$@" ;;
    *)       die "unknown command '$CMD' (expected: create | list | restore)" ;;
esac
