#!/usr/bin/env bash
# Zero-downtime blue-green swap of one compose service (default `app`).
#
# Brings up a SECOND replica of the service on the already-built image next to
# the old one, waits until the new container passes /ready, then drops the old.
# The front Caddy resolves the alias dynamically (docker/proxy/Caddyfile →
# `dynamic a`), so during the overlap traffic goes to both containers, and
# after the old one is removed — only to the new. No window without a live
# upstream.
#
# Requires COMPOSE — the docker compose invocation string with -f/--env-file:
#   COMPOSE="docker compose -f docker/compose.prod.yml --env-file .env.prod" ./ops/rollout.sh app
#
# Safety: if the new container does NOT become ready (failed migration, boot
# error) — no switch happens, the new one is removed, the script fails (exit 1)
# → the old container keeps serving. The readiness gate makes a bad deploy
# fail closed.
set -euo pipefail

SERVICE="${1:-app}"
: "${COMPOSE:?COMPOSE (docker compose invocation string) is required}"

# shellcheck disable=SC2086
compose() { $COMPOSE "$@"; }

# Opt-in supply-chain gate (ADR-0014): for registry-pulled images, refuse to
# roll out anything without verified build provenance. Requires gh CLI and
#   DEPLOY_REQUIRE_ATTESTATION=true DEPLOY_IMAGE=ghcr.io/<owner>/<repo>:<tag>
#   DEPLOY_REPO=<owner>/<repo>
# Locally-built images (default deploy.sh flow) have no registry attestation —
# the gate only applies to pull-based deploys.
if [ "${DEPLOY_REQUIRE_ATTESTATION:-false}" = "true" ]; then
    : "${DEPLOY_IMAGE:?DEPLOY_IMAGE is required when DEPLOY_REQUIRE_ATTESTATION=true}"
    : "${DEPLOY_REPO:?DEPLOY_REPO is required when DEPLOY_REQUIRE_ATTESTATION=true}"
    echo "[rollout] Verifying build provenance of ${DEPLOY_IMAGE}..."
    if ! gh attestation verify "oci://${DEPLOY_IMAGE}" --repo "${DEPLOY_REPO}"; then
        echo "[rollout] ERROR: provenance verification failed — refusing to roll out." >&2
        exit 1
    fi
    echo "[rollout] Provenance OK."
fi

old="$(compose ps -q "$SERVICE" | head -1)"
if [ -z "$old" ]; then
    echo "[rollout] '$SERVICE' is not running — first start, plain up."
    compose up -d --no-deps "$SERVICE"
    exit 0
fi
echo "[rollout] Current $SERVICE: $old"

echo "[rollout] Starting a second $SERVICE replica on the new image..."
compose up -d --no-deps --no-recreate --scale "$SERVICE"=2 "$SERVICE"

new="$(compose ps -q "$SERVICE" | grep -v "^${old}" | head -1)"
[ -n "$new" ] || { echo "[rollout] ERROR: new container not found" >&2; exit 1; }
echo "[rollout] New $SERVICE: $new — waiting for /ready..."

healthy=0
for i in $(seq 1 60); do
    if docker exec "$new" curl -fsS http://localhost/ready >/dev/null 2>&1; then
        healthy=1
        echo "[rollout] New container is ready."
        break
    fi
    echo "[rollout] ... not ready yet ($i/60)"
    sleep 2
done

if [ "$healthy" -ne 1 ]; then
    echo "[rollout] ERROR: new container never became ready. Logs:" >&2
    docker logs --tail 80 "$new" >&2 || true
    echo "[rollout] Keeping the OLD container alive, removing the failed new one." >&2
    docker rm -f "$new" >/dev/null 2>&1 || true
    exit 1
fi

echo "[rollout] Switching: removing the old container $old..."
docker rm -f "$old" >/dev/null

echo "[rollout] Normalizing scale back to 1 (without recreating the new one)..."
compose up -d --no-deps --no-recreate --scale "$SERVICE"=1 "$SERVICE"

echo "[rollout] ✅ $SERVICE replaced with zero downtime."
