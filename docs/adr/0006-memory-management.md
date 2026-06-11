---
id: ADR-0006
title: Memory Management
status: Accepted
date: 2026-02-05
supersedes: []
superseded_by: []
audience: both
summary: "Worker recycling via FRANKENPHP_LOOP_MAX (symfony/runtime, default 500 requests), memory_limit 256M and OPcache tuned in docker/php/prod-optimizations.ini. Recycling bounds slow leaks; OPcache never revalidates inside an immutable image."
---

# ADR-0006: Memory Management

**TL;DR:** Worker processes recycle after `FRANKENPHP_LOOP_MAX` requests (default 500, enforced by symfony/runtime); `memory_limit` and OPcache live in `docker/php/prod-optimizations.ini`. Without recycling, slow leaks accumulate across the worker's lifetime.

## Context

FrankenPHP worker mode keeps the Symfony kernel in memory between requests (ADR-0004). The price: any state that survives a request — static caches, leaked references, growing arrays — accumulates until the worker dies. Memory management is therefore about *bounding* growth, not eliminating it.

## Decision

### Worker recycling (the real knob)

`symfony/runtime` 8 drives the FrankenPHP worker loop natively (`FrankenPhpWorkerRunner`). Its knobs:

| Env var | Default | Effect |
|---|---|---|
| `FRANKENPHP_LOOP_MAX` | `500` | Worker exits after N requests; FrankenPHP starts a fresh one. Bounds slow leaks. |
| `FRANKENPHP_RESET_KERNEL` | off | Clone the kernel after each request to mitigate cross-request state leaks (stronger isolation, small CPU cost). |

Lower `FRANKENPHP_LOOP_MAX` if memory grows visibly between recycles; raise it when handlers are proven leak-free and you want maximum throughput.

### PHP settings (shipped in `docker/php/prod-optimizations.ini`)

```ini
memory_limit=256M
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0   ; code never changes inside a built image
realpath_cache_size=4096K
realpath_cache_ttl=600
```

Raise `memory_limit` per project by editing the ini — there is no env-var indirection on purpose: PHP ini values don't read the environment, and pretending otherwise is how dead config is born.

### Worker count

Thread/worker counts belong to the Caddy layer: the `frankenphp` global block in `docker/frankenphp/Caddyfile` (e.g. `num_threads`), or the worker line in `FRANKENPHP_CONFIG`. Defaults (2× CPU cores) are good until proven otherwise.

### Container limits

Memory caps are a deployment concern: set `mem_limit` (compose) on the host according to `workers × memory_limit + headroom`. The skeleton does not hardcode them.

## Rules for handler code

- No mutable static state (see ADR-0004). If unavoidable, reset it via `kernel.reset`.
- Stream large responses (`StreamedResponse`) instead of buffering.
- Watch `memory_get_peak_usage()` in `/metrics`-adjacent tooling when debugging (see `docs/guides/worker-mode.md`).

## Consequences

**Positive:** leaks are bounded by recycling; OPcache fully warm for a worker's whole life; one file (`prod-optimizations.ini`) owns the numbers.

**Negative:** a worker's first request after recycling pays kernel boot again; misconfigured `FRANKENPHP_LOOP_MAX=0` (never recycle) turns any slow leak into an OOM.

## References

- [FrankenPHP Worker Mode](https://frankenphp.dev/docs/worker-mode/)
- `vendor/symfony/runtime/Runner/FrankenPhpWorkerRunner.php` — the actual loop.
- ADR-0004 — FrankenPHP runtime.
