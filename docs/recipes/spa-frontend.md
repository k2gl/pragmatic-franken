---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "Replace AssetMapper+Twig with a Vue/React SPA in frontend/: a spa_builder Docker stage compiles it into the prod image; Caddy serves the app shell with history-mode fallback. Pattern proven in real production projects."
---

# Recipe: SPA frontend (Vue/React)

The skeleton's default frontend is AssetMapper + Twig (ADR-0007) — zero build
tooling. When a project outgrows it, the production-proven pattern is a `frontend/`
workspace compiled **inside the Docker build**, so deploys stay single-artifact.

## Layout

```
frontend/           # Vite + Vue 3 (or React) workspace — own package.json
  src/ …
  vite.config.ts    # dev server proxies /api and /.well-known/mercure to the app
```

Dev: `npm run dev` against the containerized API (set a `FRONTEND_PORT` in
`.env.dist` — `dev/worktree.sh` then offsets it per fork automatically).

## Docker: the spa_builder stage

```dockerfile
# after php_builder in docker/frankenphp/Dockerfile
FROM node:22-alpine AS spa_builder
WORKDIR /spa
COPY frontend/package*.json ./
RUN npm ci
COPY frontend ./
RUN npm run build              # → /spa/dist

# in php_prod:
COPY --from=spa_builder /spa/dist public/
```

## Caddy: history-mode fallback

In production the app shell must answer deep links. Add to the main site in
`docker/frankenphp/Caddyfile` (or a prod-specific one):

```caddy
@backend path /api* /healthz /ready /.well-known/mercure*
handle @backend {
    php_server
}
handle {
    try_files {path} /index.html
    file_server
}
```

## Keep the contract typed

`make open-api` exports the OpenAPI spec; `dev/generate-sdk.sh` turns Result
DTOs into TypeScript types (`packages/client-sdk/types.ts`) so the SPA speaks
the same language as the handlers. Drop `src/Context/Home/` once the SPA owns
the root route.
