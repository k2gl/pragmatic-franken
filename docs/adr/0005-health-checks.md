---
id: ADR-0005
title: Health Checks
status: Accepted
date: 2026-02-05
supersedes: []
superseded_by: []
audience: both
summary: "Standardized HTTP probes: /healthz (liveness, no dependency calls) and /ready (readiness with DB+Redis pings). Reference implementation lives in src/Context/Health/Features/Healthz/."
---

# ADR-0005: Health Checks

**TL;DR:** Two endpoints — `/healthz` (process is alive, no dependency calls) and `/ready` (dependencies reachable). The reference slice in `src/Context/Health/Features/Healthz/` is the canonical implementation pattern.

## Context

Production environments require health endpoints to:
- Verify application readiness before routing traffic
- Detect dependency failures (DB, Redis)
- Enable rolling deployments with health gates
- Map cleanly onto Kubernetes-style liveness/readiness semantics

Mixing the two probes is the classic failure mode: if liveness checks dependencies, a DB outage restart-loops every app container and makes the outage worse.

## Decision

| Endpoint | Purpose | Body |
|----------|---------|------|
| `/healthz` | Liveness — the worker responds at all. Never calls dependencies. | `{"ok": true}` |
| `/ready` | Readiness — dependencies reachable. Pings DB and Redis. | `{"ok": bool, "db": bool, "redis": bool}` + 503 when degraded |

Both live in one slice — `src/Context/Health/Features/Healthz/` — because they are facets of a single health feature:

- `EntryPoint/Http/HealthzController.php` — both routes.
- `Application/CheckHealthQuery.php` + `CheckHealthHandler.php` + `HealthStatus.php` — CQRS query for readiness.
- `Infrastructure/DbPing.php`, `RedisPing.php` (+ interfaces) — the actual pings; interfaces exist so tests substitute them.

## Who probes what

- **docker-compose (dev)**: container healthcheck hits `/ready` — compose only reports health and gates `depends_on`, it never restart-loops on unhealthy, so the richer signal is safe.
- **`docker-entrypoint.sh healthcheck`** (prod image): hits `/ready` for the same reason; deployment tooling (`ops/rollout.sh`) gates traffic switches on it.
- **CI**: the `prod-image` job boots the real image and asserts `/ready` returns `"ok":true` — the strongest end-to-end claim the template makes.
- **Kubernetes** (if you deploy there): map `livenessProbe` → `/healthz`, `readinessProbe`/`startupProbe` → `/ready`. The endpoints were named for exactly this mapping.

Caddy metrics (Prometheus) are separate from app health: the admin endpoint inside the container serves them — `make stats`. See `docs/guides/worker-mode.md`.

## Consequences

### Positive

- **No restart loops**: liveness stays green during dependency outages; readiness takes the instance out of rotation instead.
- **Deploy gates for free**: anything that can `curl /ready` can gate a rollout.
- **Test substitution**: ping interfaces let unit tests simulate degraded states.

### Negative

- **Two endpoints to keep honest** — `dev/check-docs.sh` verifies both routes exist in `src/`.
- `/ready` adds a DB+Redis round-trip per probe; keep probe intervals ≥ 10s.
