---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "Label-driven PR preview environments on the VDS: add a label → an isolated stack at pr-<N>.<domain> with fresh seed or a prod-data copy; remove it → full teardown. Pattern from the production CRM; off by default (needs VDS, wildcard DNS, secrets)."
---

# Recipe: PR preview environments

Off by default on purpose: previews need a VDS, a wildcard DNS record and
deploy secrets — shipping them enabled would break the fresh-fork
day-one-green guarantee. The pattern below runs in production in the CRM
grown from this skeleton; lift `preview.yml` + `ops/preview.sh` from there
when you adopt it.

## How it behaves (the contract worth copying)

- **Label-driven**: `preview` label on a PR → an isolated compose stack
  (fresh DB, migrations from zero, `app:seed`); `preview:prod-data` → the
  same but with a copy of the prod database — which doubles as a rehearsal of
  the PR's migrations on real data before merge.
- Every push to the PR redeploys; removing the labels or closing the PR tears
  everything down — containers, volumes, images, checkout.
- Each preview gets `https://pr-<N>.preview.<domain>` behind the front proxy
  with basic auth; the GitHub Deployment API shows the URL on the PR.

## Infrastructure it builds on (already in this skeleton)

- `docker/compose.prod.yml` — the per-environment stack previews instantiate
  with `COMPOSE_PROJECT_NAME=preview-pr<N>`.
- `docker/proxy/` — the front Caddy; previews use **on-demand TLS** with an
  `ask` allowlist endpoint so wildcard SNI can't burn the Let's Encrypt rate
  limit (the preview script creates/removes per-host marker files).
- `ops/rollout.sh` — previews reuse the same readiness gate.

## Security notes

Basic-auth the preview vhosts; previews with prod data are PII — restrict the
label to maintainers (GitHub label permissions follow push access) and keep
teardown automatic. Never reuse prod secrets: previews get their own
generated `APP_SECRET`/`MERCURE_JWT_SECRET`.
