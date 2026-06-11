---
audience: human
tier: 3
last_reviewed: 2026-06-11
summary: "Wave-based roadmap: hygiene/day-one-green → CRM-proven backports → supply-chain & agent differentiators → distribution. Human-only — load on demand for planning context, not for code generation."
---

# Pragmatic Franken Roadmap

> **"Stop refactoring. Start delivering."**

The roadmap is organized in waves, each shipping as a release. Grounded in
production experience from a real CRM grown out of this skeleton: ~25 of its
first 40 commits were infrastructure repair — every item below either ports a
proven piece back or removes that fork tax up front.

## Wave 1 — Hygiene & day-one-green (v1.0)

The template's promises become CI-proven facts.

- ✅ Production image boots — verified by the `prod-image` CI job on every PR
- ✅ One honest pipeline: hard gates for `composer audit`, Trivy, Gitleaks; zero `continue-on-error`
- ✅ Single env truth: dotenv + `.env.dist`, no compose duplication, loopback var-driven ports
- ✅ Tests on PostgreSQL with a `_test` dbname suffix (sqlite split removed)
- ✅ `/healthz` (liveness) vs `/ready` (readiness) per ADR-0005, all probes honest
- ✅ Working Mercure hub (private Caddy site, reverse-proxied under the TLS origin)
- ✅ `dev/check-docs.sh` verifies doc claims against reality (make targets, routes, ADR table)
- ✅ Community floor: LICENSE, SECURITY.md, CODEOWNERS, dependabot, release-please

## Wave 2 — CRM-proven backports (v1.1–1.2)

- `src/SharedKernel/` — typed Doctrine repository base, RFC 9457 problem+json
  exception listeners, worker heartbeat, universal seed command
- `src/Context/{Name}/` layout with context-level `Entity/`/`Repository/` (ADR-0012/0013)
- **Task example slice** — the missing real-entity reference: entity → migration →
  repository → factory → validated 422 → tests, wired to Mercure live updates
- Test kit: `sendJsonRequest()`, typed `responseReader()`, violation asserts, auth hook
- Ops: blue-green `rollout.sh` with health gate, `backup.sh`, prod/proxy compose, deployment & disaster-recovery guides
- `dev/worktree.sh` — parallel isolated stacks for multi-agent development
- Observability compose profile (Prometheus + Grafana), CI coverage floor

## Wave 3 — Differentiators: supply chain & agents (v1.3–1.4)

- Build provenance attestations on release images (GitHub Artifact Attestations)
- Opt-in deploy gate: verify image provenance before rollout (`gh attestation verify`
  and the dogfooded `app:verify-attestation` on `k2gl/sigstore-verify`)
- ADR-0018 supply-chain policy + guide
- `symfony/scheduler` example in worker mode (honest `SCHEDULER_ENABLED` path)
- `dev/agent-smoke.sh` as a required CI job — "scaffolded code passes PHPStan 10
  and tests untouched" becomes a verifiable badge, not a vibe
- `docs/recipes/` — JWT auth, feature flags, SPA frontend, preview environments
- `make init` — fork identity: rename, generated secrets, prune examples

## Wave 4 — Distribution (v2.0)

- Packagist: `composer create-project k2gl/pragmatic-franken`
- README repositioning: two-path quickstart, honest comparison vs
  symfony/skeleton / symfony-docker / API Platform, declared non-goals
- `UPGRADE.md` + fork-maintenance guide (templates aren't merged — changes are applied)
- Launch posts (EN/RU) built on the verifiable claims above

## Non-goals

Authentication in core, bundled SPA, feature-flag tables, preview environments
by default, Kubernetes/Helm, multi-DB support, composer-dependency signature
verification (no Packagist attestation ecosystem yet), per-IDE AI rule files,
and anything whose example CI cannot execute.
