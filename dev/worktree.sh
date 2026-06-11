#!/usr/bin/env bash
# Manage isolated git-worktree dev stacks so several Claude/dev sessions can run
# this project in parallel without colliding on the git tree, host ports or the
# docker project (containers/volumes/DB).
#
# Each fork = own git worktree (own branch, own dir under /www/wt/<prefix>-<slug>)
# + own docker stack (distinct COMPOSE_PROJECT_NAME + a port slot offset from the
# bases in .env.dist). This script is the single source of truth for that scheme;
# `sf worktrees` is only a read-only cross-project view layered on `ls --json`.
#
# Usage:
#   dev/worktree.sh new <slug> [branch]   Create a fork, bootstrap and start it
#   dev/worktree.sh dir <slug>            Print a fork's working dir (path scheme; used by sf claude)
#   dev/worktree.sh ls [--json]           List all forks (state, ports, dirty/ahead)
#   dev/worktree.sh status [slug] [--json]  Health + ports of one/all forks
#   dev/worktree.sh open <slug>           Open the fork's web UI (xdg-open / print URL)
#   dev/worktree.sh up <slug>             Start a fork's stack
#   dev/worktree.sh down <slug>           Stop a fork's stack (keeps its DB volume)
#   dev/worktree.sh down-all              Stop every fork stack (frees RAM)
#   dev/worktree.sh rm <slug> [--force]   Tear down stack + remove worktree
#   dev/worktree.sh prune [--days N] [--yes]  GC merged+clean+old forks; keep dirty/ahead
#
# Safety: only ever touches projects named <prefix>-<slug>; never the primary
# stack. `rm`/`prune` refuse to drop a fork with uncommitted or unmerged work
# unless --force.
set -euo pipefail

# ---------------------------------------------------------------------------
# Locate repo + read the base scheme from .env.dist (single source of truth).
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_DIST="$REPO_ROOT/.env.dist"
WT_PARENT="${WT_PARENT:-/www/wt}"
BASE_BRANCH="${WT_BASE_BRANCH:-main}"

[ -f "$ENV_DIST" ] || { echo "worktree: $ENV_DIST not found" >&2; exit 1; }

envdist() { grep -E "^$1=" "$ENV_DIST" | head -1 | cut -d= -f2-; }

PREFIX="$(envdist COMPOSE_PROJECT_NAME)"; PREFIX="${PREFIX:-pfranken}"
BASE_HTTP="$(envdist HTTP_PORT)"
BASE_HTTPS="$(envdist HTTPS_PORT)"
BASE_XDEBUG="$(envdist XDEBUG_PORT)"
BASE_DB="$(envdist DB_PORT)"
BASE_REDIS="$(envdist REDIS_PORT)"
BASE_FRONTEND="$(envdist FRONTEND_PORT)"
BASE_SERVER="$(envdist SERVER_NAME)"
DOMAIN="${BASE_SERVER#*.}"; DOMAIN="${DOMAIN:-localhost}"

# Per-slot port formula. HTTP(S) step 10 leaves headroom; the rest step 1.
port_http()   { echo $((BASE_HTTP   + $1 * 10)); }
port_https()  { echo $((BASE_HTTPS  + $1 * 10)); }
port_xdebug() { echo $((BASE_XDEBUG + $1)); }
port_db()     { echo $((BASE_DB     + $1)); }
port_redis()  { echo $((BASE_REDIS  + $1)); }
port_frontend() { [ -n "$BASE_FRONTEND" ] && echo $((BASE_FRONTEND + $1 * 10)) || echo ""; }

# ---------------------------------------------------------------------------
# Small helpers.
# ---------------------------------------------------------------------------
log()  { printf '\033[36m[worktree]\033[0m %s\n' "$*"; }
warn() { printf '\033[33m[worktree]\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[31m[worktree]\033[0m %s\n' "$*" >&2; exit 1; }

fork_dir()  { echo "$WT_PARENT/$PREFIX-$1"; }
slug_of()   { local b; b="$(basename "$1")"; echo "${b#"$PREFIX"-}"; }

# Run docker compose inside a fork dir (its .env selects project + ports).
dc() { local dir="$1"; shift; ( cd "$dir" && docker compose "$@" ); }

# Read a key from a fork's generated .env.
fork_env() { grep -E "^$2=" "$1/.env" 2>/dev/null | head -1 | cut -d= -f2-; }

# Frontend (vite) port of a fork: its FRONTEND_PORT, or — for forks created
# before that key existed — derived from the HTTPS slot, so listings stay valid.
fork_frontend_port() {
    local p; p="$(fork_env "$1" FRONTEND_PORT)"
    if [ -z "$p" ]; then
        local https; https="$(fork_env "$1" HTTPS_PORT)"
        [ -n "$https" ] && p="$(port_frontend $(( (https - BASE_HTTPS) / 10 )) )"
    fi
    echo "$p"
}

# All fork worktrees (basename <prefix>-*); the primary repo (basename <prefix>) is excluded.
fork_dirs() {
    git -C "$REPO_ROOT" worktree list --porcelain 2>/dev/null \
        | awk '/^worktree /{print $2}' \
        | while read -r p; do
            case "$(basename "$p")" in "$PREFIX"-*) echo "$p" ;; esac
        done
}

port_in_use() {
    local p="$1"
    if command -v ss >/dev/null 2>&1; then
        ss -Hltn "sport = :$p" 2>/dev/null | grep -q . && return 0 || return 1
    fi
    (exec 3<>"/dev/tcp/127.0.0.1/$p") 2>/dev/null && { exec 3>&- 3<&-; return 0; } || return 1
}

# Slots already claimed by existing forks (derived from their HTTPS_PORT), so a
# DOWN fork's ports are never reused while its stack can still come back up.
claimed_slots() {
    local d https
    for d in $(fork_dirs); do
        https="$(fork_env "$d" HTTPS_PORT)"
        [ -n "$https" ] && echo $(( (https - BASE_HTTPS) / 10 ))
    done
}

pick_slot() {
    local claimed n
    claimed=" $(claimed_slots | tr '\n' ' ') "
    for n in $(seq 1 50); do
        case "$claimed" in *" $n "*) continue ;; esac
        port_in_use "$(port_https  "$n")" && continue
        port_in_use "$(port_http   "$n")" && continue
        port_in_use "$(port_db     "$n")" && continue
        port_in_use "$(port_redis  "$n")" && continue
        port_in_use "$(port_xdebug "$n")" && continue
        if [ -n "$BASE_FRONTEND" ]; then port_in_use "$(port_frontend "$n")" && continue; fi
        echo "$n"; return 0
    done
    die "no free port slot in 1..50 — run 'prune' or 'down-all' to reclaim"
}

set_env() { # file key value  (idempotent; escapes sed replacement specials)
    local f="$1" k="$2" v="$3"
    v="${v//\\/\\\\}"; v="${v//|/\\|}"; v="${v//&/\\&}"
    if grep -qE "^$k=" "$f"; then sed -i "s|^$k=.*|$k=$v|" "$f"; else printf '%s=%s\n' "$k" "$v" >>"$f"; fi
}

# The dev containers run as root, so vendor/var land root-owned. Hand the tree
# back to the host user (throwaway container) before `git worktree remove`, else
# removal fails with EACCES on those files.
chown_tree() {
    docker run --rm -v "$1:/mnt" postgres:17-alpine \
        chown -R "$(id -u):$(id -g)" /mnt >/dev/null 2>&1 || true
}

is_dirty() { [ -n "$(git -C "$1" status --porcelain 2>/dev/null)" ]; }
ahead_of_base() { git -C "$1" rev-list --count "$BASE_BRANCH..HEAD" 2>/dev/null || echo '?'; }
is_running() { [ -n "$(dc "$1" ps --status=running -q app 2>/dev/null || true)" ]; }

# Wait until a fork's app container is running (not restarting) before `exec`.
wait_running() {
    local i
    for i in $(seq 1 30); do
        is_running "$1" && return 0
        sleep 1
    done
    return 1
}

image_exists() { docker image inspect "$1" >/dev/null 2>&1; }

# Every fork runs the same dev image (app code is bind-mounted), so reuse the
# primary's already-built image instead of recompiling PHP/xdebug per fork.
ensure_images() { # dir proj
    local dir="$1" proj="$2" svc
    for svc in $(dc "$dir" config --services 2>/dev/null | grep -E '^(app|worker)$'); do
        image_exists "$proj-$svc" && continue
        if image_exists "$PREFIX-$svc"; then
            log "reusing image $PREFIX-$svc → $proj-$svc"
            docker tag "$PREFIX-$svc" "$proj-$svc"
        else
            log "building $svc image (no prebuilt $PREFIX-$svc; one-time)…"
            dc "$dir" build "$svc"
        fi
    done
}

fork_health() { # dir -> ok|bad ; only call when running
    local dir="$1" server https
    server="$(fork_env "$dir" SERVER_NAME)"; https="$(fork_env "$dir" HTTPS_PORT)"
    if curl -fsS -k --max-time 3 --resolve "$server:$https:127.0.0.1" \
        "https://$server:$https/ready" >/dev/null 2>&1; then echo ok; else echo bad; fi
}

require_slug() { [ -n "${1:-}" ] || die "slug required"; }
ensure_fork()  { [ -d "$1" ] || die "no fork at $1 (see 'ls')"; }

# ---------------------------------------------------------------------------
# Commands.
# ---------------------------------------------------------------------------
cmd_new() {
    local slug="${1:-}" branch="${2:-}"
    require_slug "$slug"
    [[ "$slug" =~ ^[a-z0-9][a-z0-9-]*$ ]] || die "slug must be [a-z0-9-], got '$slug'"
    local dir; dir="$(fork_dir "$slug")"
    [ -e "$dir" ] && die "$dir already exists"
    local proj="$PREFIX-$slug" server="$PREFIX-$slug.$DOMAIN"
    local n; n="$(pick_slot)"

    log "Creating worktree $dir (slot $n, project $proj)…"
    mkdir -p "$WT_PARENT"
    local branch_name="${branch:-wt/$slug}"
    if git -C "$REPO_ROOT" show-ref --verify --quiet "refs/heads/$branch_name"; then
        git -C "$REPO_ROOT" worktree add "$dir" "$branch_name"   # reuse existing branch
    else
        git -C "$REPO_ROOT" worktree add -b "$branch_name" "$dir" "$BASE_BRANCH"
    fi

    log "Writing isolated .env (ports offset by slot $n)…"
    cp "$ENV_DIST" "$dir/.env"
    set_env "$dir/.env" UID "$(id -u)"
    set_env "$dir/.env" GID "$(id -g)"
    set_env "$dir/.env" COMPOSE_PROJECT_NAME "$proj"
    set_env "$dir/.env" IMAGES_PREFIX "$proj"
    set_env "$dir/.env" SERVER_NAME "$server"
    set_env "$dir/.env" HTTP_PORT   "$(port_http "$n")"
    set_env "$dir/.env" HTTPS_PORT  "$(port_https "$n")"
    set_env "$dir/.env" HTTP3_PORT  "$(port_https "$n")"
    set_env "$dir/.env" XDEBUG_PORT "$(port_xdebug "$n")"
    set_env "$dir/.env" DB_PORT     "$(port_db "$n")"
    set_env "$dir/.env" REDIS_PORT  "$(port_redis "$n")"
    [ -n "$BASE_FRONTEND" ] && set_env "$dir/.env" FRONTEND_PORT "$(port_frontend "$n")"
    # MERCURE_URL stays the in-container hub address (http://localhost:3000/…);
    # only the browser-facing subscribe URL follows the fork's HTTPS slot.
    set_env "$dir/.env" MERCURE_PUBLIC_URL "https://$server:$(port_https "$n")/.well-known/mercure"

    # vendor/ and config/jwt/*.pem are git-ignored, so a fresh worktree has neither.
    # The bind mount (./:/app) shadows the image's baked vendor.
    log "Preparing image…"
    ensure_images "$dir" "$proj"
    # Provision vendor on the host BEFORE boot: a missing autoloader crashloops the
    # app, and a crashlooping container also rejects `exec`. Copy the primary's vendor
    # (identical code/lock at fork time; also lands host-owned) instead of a
    # memory-heavy per-fork composer install. Changed deps later → composer install in it.
    if [ -d "$REPO_ROOT/vendor" ]; then
        log "Copying vendor from primary (skips composer install)…"
        cp -a "$REPO_ROOT/vendor" "$dir/vendor"
    fi
    log "Starting stack…"
    dc "$dir" up -d
    if [ ! -d "$dir/vendor" ]; then
        log "No primary vendor — running composer install…"
        dc "$dir" exec -T app composer install --no-interaction
        dc "$dir" restart app worker
    fi
    wait_running "$dir" || warn "app slow to start; continuing"
    log "Generating JWT keypair…"
    dc "$dir" exec -T app php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction
    log "Migrating database…"
    dc "$dir" exec -T app php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    log "Seeding demo data…"
    dc "$dir" exec -T app php bin/console app:seed || warn "seed failed (non-fatal)"

    local url="https://$server:$(port_https "$n")"
    log "Verifying health…"
    [ "$(fork_health "$dir")" = ok ] && log "/ready OK" || warn "/ready not green yet (may need a few seconds; or VPN blocks host→container — check from inside)"

    echo
    log "Fork '$slug' ready:"
    printf '  URL:    %s\n' "$url"
    printf '  dir:    %s\n' "$dir"
    printf '  ports:  https=%s http=%s db=%s redis=%s xdebug=%s\n' \
        "$(port_https "$n")" "$(port_http "$n")" "$(port_db "$n")" "$(port_redis "$n")" "$(port_xdebug "$n")"
    printf '  agent:  start your AI session inside %s — it picks up AGENTS.md\n' "$dir"
}

cmd_ls() {
    local json=0; [ "${1:-}" = "--json" ] && json=1
    local dirs; dirs="$(fork_dirs)"
    if [ "$json" = 1 ]; then
        local first=1; printf '['
        local d; for d in $dirs; do
            [ "$first" = 1 ] && first=0 || printf ','
            local slug branch running health ahead
            slug="$(slug_of "$d")"
            branch="$(git -C "$d" rev-parse --abbrev-ref HEAD 2>/dev/null || echo '?')"
            if is_running "$d"; then running=true; health="\"$(fork_health "$d")\""; else running=false; health=null; fi
            ahead="$(ahead_of_base "$d")"; [[ "$ahead" =~ ^[0-9]+$ ]] || ahead=null
            printf '{"slug":"%s","branch":"%s","dir":"%s","project":"%s","url":"https://%s:%s","front_url":"http://%s:%s","running":%s,"health":%s,"dirty":%s,"ahead":%s,"age":"%s","ports":{"http":%s,"https":%s,"db":%s,"redis":%s,"xdebug":%s,"frontend":%s}}' \
                "$slug" "$branch" "$d" "$(fork_env "$d" COMPOSE_PROJECT_NAME)" \
                "$(fork_env "$d" SERVER_NAME)" "$(fork_env "$d" HTTPS_PORT)" \
                "$(fork_env "$d" SERVER_NAME)" "$(fork_frontend_port "$d")" \
                "$running" "$health" \
                "$(is_dirty "$d" && echo true || echo false)" "$ahead" \
                "$(git -C "$d" log -1 --format=%cr 2>/dev/null || echo '?')" \
                "$(fork_env "$d" HTTP_PORT)" "$(fork_env "$d" HTTPS_PORT)" \
                "$(fork_env "$d" DB_PORT)" "$(fork_env "$d" REDIS_PORT)" "$(fork_env "$d" XDEBUG_PORT)" \
                "$(fork_frontend_port "$d")"
        done
        printf ']\n'
        return
    fi
    [ -n "$dirs" ] || { echo "no forks (create one: dev/worktree.sh new <slug>)"; return; }
    { printf 'SLUG\tBRANCH\tSTATE\tHEALTH\tDIRTY\tAHEAD\tURL\tFRONT\tAGE\n'
      local d; for d in $dirs; do
        local state health
        if is_running "$d"; then state=up; health="$(fork_health "$d")"; else state=down; health=-; fi
        printf '%s\t%s\t%s\t%s\t%s\t%s\thttps://%s:%s\thttp://%s:%s\t%s\n' \
            "$(slug_of "$d")" "$(git -C "$d" rev-parse --abbrev-ref HEAD 2>/dev/null || echo '?')" \
            "$state" "$health" \
            "$(is_dirty "$d" && echo yes || echo -)" "$(ahead_of_base "$d")" \
            "$(fork_env "$d" SERVER_NAME)" "$(fork_env "$d" HTTPS_PORT)" \
            "$(fork_env "$d" SERVER_NAME)" "$(fork_frontend_port "$d")" \
            "$(git -C "$d" log -1 --format=%cr 2>/dev/null || echo '?')"
      done
    } | { command -v column >/dev/null 2>&1 && column -t -s $'\t' || cat; }
}

cmd_status() {
    local slug="" json=""
    for a in "$@"; do case "$a" in --json) json=--json ;; *) slug="$a" ;; esac; done
    if [ -n "$slug" ] && [ -z "$json" ]; then
        local dir; dir="$(fork_dir "$slug")"; ensure_fork "$dir"
        local state=down health=-
        is_running "$dir" && { state=up; health="$(fork_health "$dir")"; }
        printf 'slug:    %s\nstate:   %s\nhealth:  %s\nurl:     https://%s:%s\nfront:   http://%s:%s\nports:   http=%s db=%s redis=%s xdebug=%s frontend=%s\n' \
            "$slug" "$state" "$health" "$(fork_env "$dir" SERVER_NAME)" "$(fork_env "$dir" HTTPS_PORT)" \
            "$(fork_env "$dir" SERVER_NAME)" "$(fork_frontend_port "$dir")" \
            "$(fork_env "$dir" HTTP_PORT)" "$(fork_env "$dir" DB_PORT)" "$(fork_env "$dir" REDIS_PORT)" "$(fork_env "$dir" XDEBUG_PORT)" \
            "$(fork_frontend_port "$dir")"
        return
    fi
    cmd_ls $json
}

cmd_open() {
    require_slug "${1:-}"
    local dir; dir="$(fork_dir "$1")"; ensure_fork "$dir"
    is_running "$dir" || { log "stack down — starting…"; dc "$dir" up -d; }
    local url="https://$(fork_env "$dir" SERVER_NAME):$(fork_env "$dir" HTTPS_PORT)"
    if command -v xdg-open >/dev/null 2>&1 && [ -n "${DISPLAY:-}" ]; then
        log "opening $url"; xdg-open "$url" >/dev/null 2>&1 &
    else
        log "open this URL: $url"
        curl -fsS -k --max-time 3 --resolve "$(fork_env "$dir" SERVER_NAME):$(fork_env "$dir" HTTPS_PORT):127.0.0.1" "$url/ready" && echo || warn "/ready not reachable"
    fi
}

cmd_dir()  { require_slug "${1:-}"; fork_dir "$1"; }   # pure: print path even if not created yet
cmd_up()   { require_slug "${1:-}"; local d; d="$(fork_dir "$1")"; ensure_fork "$d"; dc "$d" up -d; log "$1 up"; }
cmd_down() { require_slug "${1:-}"; local d; d="$(fork_dir "$1")"; ensure_fork "$d"; dc "$d" down --remove-orphans; log "$1 down (DB volume kept)"; }

cmd_down_all() {
    local d; for d in $(fork_dirs); do
        is_running "$d" && { log "stopping $(slug_of "$d")…"; dc "$d" down --remove-orphans; }
    done
    log "all fork stacks stopped (primary stack untouched)"
}

cmd_rm() {
    local slug="" force=0
    for a in "$@"; do case "$a" in --force) force=1 ;; *) slug="$a" ;; esac; done
    require_slug "$slug"
    local dir; dir="$(fork_dir "$slug")"; ensure_fork "$dir"
    if [ "$force" = 0 ]; then
        is_dirty "$dir" && die "$slug has uncommitted changes — commit/stash or pass --force"
        [ "$(ahead_of_base "$dir")" != 0 ] && die "$slug has commits not in $BASE_BRANCH — merge/push or pass --force"
    fi
    log "tearing down stack + volumes for $slug…"
    dc "$dir" down -v --remove-orphans || true
    chown_tree "$dir"
    git -C "$REPO_ROOT" worktree remove ${force:+--force} "$dir"
    log "removed worktree $dir (branch kept; delete with: git branch -D <branch>)"
}

cmd_prune() {
    local days=14 yes=0
    while [ $# -gt 0 ]; do case "$1" in
        --days) days="$2"; shift 2 ;;
        --yes)  yes=1; shift ;;
        *) die "prune: unknown arg $1" ;;
    esac; done

    local now; now="$(date +%s)"
    local candidates=()
    local d; for d in $(fork_dirs); do
        local slug reason="" age_days ahead
        slug="$(slug_of "$d")"
        ahead="$(ahead_of_base "$d")"
        local ct; ct="$(git -C "$d" log -1 --format=%ct 2>/dev/null || echo "$now")"
        age_days=$(( (now - ct) / 86400 ))
        if is_dirty "$d"; then reason="dirty"
        elif [ "$ahead" != 0 ]; then reason="ahead $ahead"
        elif [ "$age_days" -lt "$days" ]; then reason="age ${age_days}d < ${days}d"
        fi
        if [ -n "$reason" ]; then
            printf 'keep   %-16s (%s)\n' "$slug" "$reason"
        else
            printf 'PRUNE  %-16s (clean, merged, %sd old)\n' "$slug" "$age_days"
            candidates+=("$d")
        fi
    done

    # Orphan docker projects (<prefix>-*) whose worktree is gone.
    local live_slugs=" "; for d in $(fork_dirs); do live_slugs+="$(slug_of "$d") "; done
    local orphans=()
    while read -r proj; do
        [ -n "$proj" ] || continue
        case "$proj" in "$PREFIX"-*) ;; *) continue ;; esac
        local s="${proj#"$PREFIX"-}"
        case "$live_slugs" in *" $s "*) ;; *) orphans+=("$proj"); printf 'ORPHAN %-16s (docker project, no worktree)\n' "$s" ;; esac
    done < <(docker compose ls --all --format json 2>/dev/null | grep -o '"Name":"[^"]*"' | cut -d'"' -f4 || true)

    if [ "$yes" != 1 ]; then
        echo; echo "(dry-run) re-run with --yes to remove PRUNE/ORPHAN entries above"
        return
    fi
    for d in "${candidates[@]:-}"; do [ -n "$d" ] || continue; log "removing $(slug_of "$d")…"; dc "$d" down -v --remove-orphans || true; chown_tree "$d"; git -C "$REPO_ROOT" worktree remove --force "$d" || true; done
    for proj in "${orphans[@]:-}"; do [ -n "$proj" ] || continue; log "stopping orphan project $proj…"; docker compose -p "$proj" down -v --remove-orphans || true; done
    git -C "$REPO_ROOT" worktree prune
    log "prune done"
}

usage() { sed -n '2,30p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'; }

# ---------------------------------------------------------------------------
main() {
    local cmd="${1:-}"; shift || true
    case "$cmd" in
        new)        cmd_new "$@" ;;
        dir)        cmd_dir "$@" ;;
        ls|list)    cmd_ls "$@" ;;
        status|st)  cmd_status "$@" ;;
        open)       cmd_open "$@" ;;
        up)         cmd_up "$@" ;;
        down)       cmd_down "$@" ;;
        down-all)   cmd_down_all ;;
        rm|remove)  cmd_rm "$@" ;;
        prune|gc)   cmd_prune "$@" ;;
        ""|-h|--help|help) usage ;;
        *)          die "unknown command '$cmd' (try --help)" ;;
    esac
}
main "$@"
