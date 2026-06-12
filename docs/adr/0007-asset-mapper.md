---
id: ADR-0007
title: AssetMapper
status: Accepted
date: 2026-02-05
supersedes: []
superseded_by: []
audience: both
summary: "Symfony AssetMapper is the default for HTML-first apps (no Webpack/Vite). The boilerplate ships an Index slice as a non-normative reference; SPA projects may swap AssetMapper for Vite without violating any ADR."
---

# ADR-0007: AssetMapper

**TL;DR:** Default frontend tooling is AssetMapper. The shipped `src/Context/Home/Features/Index/` slice is a reference example, **not** an architectural commitment. Projects building an SPA can replace it with a `frontend/` workspace driven by Vite without violating any other ADR (see `docs/recipes/spa-frontend.md`).

## Decision

Use Symfony AssetMapper as the default for managing frontend assets in HTML-first slices.

## Context

We needed asset management that works without build tools (Webpack, Vite), provides hot reload in development, and runs seamlessly under FrankenPHP.

## Consequences

### Positive

- **Zero build configuration** — works out of the box with PHP
- **Hot reload** in development; automatic content-hash versioning
- **Import Maps** — native browser ES modules

### Negative

- **Limited transpilation** — not suitable for complex JS frameworks; SPAs swap in Vite (recipe above)
- **Browser support** — requires modern browsers with Import Maps

Shipped usage lives in the `Home/Index` slice and `assets/` — delete both without architectural impact when going API-only or SPA.

## References

- [Symfony AssetMapper Documentation](https://symfony.com/doc/current/frontend/asset_mapper.html)
