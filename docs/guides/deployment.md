---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "Single-VDS deployment: stage + prod as isolated compose projects behind one front Caddy that terminates TLS; zero-downtime blue-green rollout gated on /ready. Ported from the production CRM grown out of this skeleton."
---

# Deployment — single VDS, stage + prod

One VDS hosts two isolated environments (`stage`, `prod`) fronted by a Caddy
reverse proxy that terminates TLS. Everything below is ported from a real
production setup.

## Topology

```
            Internet :80/:443 (+:443/udp)
                      │
              ┌───────▼────────┐   proxy (docker/compose.proxy.yml)
              │  front Caddy   │   auto-TLS via Let's Encrypt, routes by host
              └───┬────────┬───┘   on the shared docker network
       app-stage  │        │  app-prod        (APP_ALIAS per environment)
      ┌───────────▼──┐  ┌──▼─────────────┐
      │ stage stack  │  │  prod stack    │    docker/compose.prod.yml
      │ app db redis │  │ app db redis   │    + worker (messenger consumer)
      │ worker       │  │ worker         │
      └──────────────┘  └────────────────┘
```

- The per-env app containers listen on plain `:80` (`SERVER_NAME=:80`,
  `auto_https off`) — the proxy owns the host's 80/443.
- `COMPOSE_PROJECT_NAME` from each `.env.<env>` isolates containers/volumes.
- The proxy resolves `APP_ALIAS` dynamically, which is what makes blue-green
  rollouts seamless (both replicas answer during the swap).

## One-time VDS setup

```bash
docker network create app-shared

mkdir -p /srv/app && cd /srv/app
git clone <repo> stage && git clone <repo> prod
mkdir proxy && cp prod/docker/compose.proxy.yml prod/docker/proxy/Caddyfile proxy/
# proxy/.env: APEX_DOMAIN / PROD_DOMAIN / STAGE_DOMAIN
(cd proxy && docker compose -f compose.proxy.yml up -d)

# Per environment: .env.<env> next to the checkout (never committed):
#   COMPOSE_PROJECT_NAME=app-prod   APP_ALIAS=app-prod
#   APP_SECRET=<openssl rand -hex 32>   POSTGRES_PASSWORD=<openssl rand -hex 32>
#   MERCURE_JWT_SECRET=<openssl rand -hex 32>
#   MERCURE_PUBLIC_URL=https://app.example.com/.well-known/mercure
```

## Deploying

```bash
cd /srv/app/stage && ENVIRONMENT=stage REF=origin/main ./ops/deploy.sh
cd /srv/app/prod  && ENVIRONMENT=prod  REF=v1.2.3      ./ops/deploy.sh
```

`deploy.sh` fetches the ref, builds `php_prod` (`--pull` for base-image
patches), takes a pre-migration dump (prod), brings up db/redis/worker, then
calls `ops/rollout.sh app` — the new container must pass `/ready` before the
old one is removed; a failing deploy leaves the old container serving.

**Migrations must be backward-compatible** (expand/contract): during the
blue-green overlap the old code runs on the already-migrated schema.

## CI/CD

`release.yml` builds, boot-tests and pushes the image on every main commit and
tag. Hook deployment to your taste — the CRM triggers `deploy.sh` over SSH:
stage auto-deploys on push to main; prod is a manual `workflow_dispatch` with
`REF=<tag>` after stage soak. Verify image provenance before rollout — see
`docs/guides/supply-chain.md`.

## Verification drill

During a rollout, run `while curl -fsS https://stage.../ready; do sleep 0.2; done`
from outside: zero non-200 responses is the acceptance criterion.
