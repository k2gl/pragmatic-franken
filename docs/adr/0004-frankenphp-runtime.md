---
id: ADR-0004
title: FrankenPHP Runtime
status: Accepted
date: 2026-02-04
supersedes: []
superseded_by: []
audience: both
summary: "FrankenPHP (Caddy + PHP) is the primary application server with worker mode. Single binary, HTTP/3, no separate consumer processes for Messenger workers."
---

# ADR-0004: FrankenPHP Runtime

**TL;DR:** FrankenPHP runs the app in worker mode (kernel kept in memory between requests). Throughput is 2–3× PHP-FPM. Trade-off: handlers must be stateless. See `docs/guides/worker-mode.md` for operational guidance.

## Decision

Use FrankenPHP as the primary application server, in worker mode.

## Context

We needed a modern application server: better throughput than PHP-FPM, native worker mode (no separate consumer processes for Messenger), HTTP/3 with automatic HTTPS, and a built-in Mercure hub.

## Consequences

**Positive:** worker mode removes per-request bootstrap; a single binary replaces PHP-FPM + web server + Supervisor; HTTP/3 and automatic HTTPS out of the box; built-in Mercure hub for real-time updates.

**Negative:** learning curve for developers accustomed to PHP-FPM; debugging requires understanding the worker lifecycle; handlers must be stateless.

## Stateless design

Worker mode keeps the kernel — and anything static — alive across requests: no static request state, no request-keyed in-memory caches, external cache (Redis) for shared state. Patterns, pitfalls and per-request cleanup live in `docs/guides/worker-mode.md`; memory bounds and worker recycling in ADR-0006.

## Worker configuration

Worker count and server config live in `docker/frankenphp/Caddyfile`; memory and OPcache tuning in `docker/php/prod-optimizations.ini` (ADR-0006).

## Alternatives Considered

- **RoadRunner** — good performance, but a separate binary and process model
- **Swoole** — excellent performance, limited Symfony integration
- **PHP-FPM** — standard, but no worker mode

## References

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [Symfony Runtime Component](https://symfony.com/doc/current/runtime.html)
