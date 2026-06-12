---
id: ADR-0016
title: HTTP Response Contract
status: Accepted
date: 2026-06-12
supersedes: []
superseded_by: []
audience: both
summary: "Success JSON bodies are wrapped in a {\"data\": ...} envelope; errors are RFC 9457 problem+json from the SharedKernel listeners. Health probes and server-rendered HTML are exempt. Status codes keep their semantics (201, 404, 422)."
---

# ADR-0016: HTTP Response Contract

**TL;DR:** Every successful JSON API response wraps its payload: `{"data": <Result DTO>}`. Errors never use the envelope — they are RFC 9457 `application/problem+json` produced by the SharedKernel exception listeners (ADR-0009). Probes (`/healthz`, `/ready`) and Twig pages are out of scope. Production-proven.

## Context

Clients and generated SDKs need one predictable parse path for success and one for failure. Returning bare DTOs works until the first endpoint needs pagination or metadata — then the shape forks per endpoint. An envelope fixes the shape on day one and leaves an extension point. The scaffolder already emitted the envelope; the shipped examples now match it instead of contradicting it.

## Decision

### 1. Success: the `data` envelope

```php
return new JsonResponse(['data' => $result], Response::HTTP_CREATED);
```

- The value under `data` is the slice's Result DTO (or a structure of DTOs, e.g. `{"data": {"items": [...]}}` for lists).
- Status codes carry semantics as usual: `200`, `201` for creation, `202` for accepted async work.
- Future cross-cutting metadata (pagination, request ids) goes into sibling top-level keys (`meta`), never inside `data`.

### 2. Failure: problem+json, no envelope

Errors keep RFC 9457 `application/problem+json` with top-level `type`, `title`, `status`, plus `violations` for 422 — produced centrally by the SharedKernel listeners (ADR-0009). Clients branch on status code, then parse one of exactly two shapes.

### 3. Exemptions

| Surface | Shape |
|---|---|
| `/healthz`, `/ready` | bare `{"ok": ...}` — probes are consumed by infra (`ops/rollout.sh`, compose healthchecks, CI greps), not API clients |
| Server-rendered HTML (Twig slices) | not JSON |
| Mercure event payloads | topic-specific JSON, versioned by topic |

### 4. Input direction

The input contract is the `*Request` DTO in `Application/Dto/`, validated via `#[MapRequestPayload]`; the controller builds the command from it (ADR-0001 §2). Validation failures surface as 422 problem+json automatically.

## Consequences

### Positive

- One unwrap path for clients and the generated TypeScript SDK (`docs/guides/sdk-generation.md`).
- Pagination/metadata can be added without breaking existing consumers.
- Error handling is centralised; slices cannot invent bespoke error shapes.

### Negative

- One extra level of nesting for trivial endpoints.
- Existing raw-shape clients (pre-contract) must switch to `.data` once.

## References

- ADR-0009 — problem+json listeners in the SharedKernel.
- `src/Context/Task/Features/CreateTask/EntryPoint/Http/CreateTaskController.php` — reference implementation.
- `dev/create-slice.sh` — the scaffolder emits the envelope.
