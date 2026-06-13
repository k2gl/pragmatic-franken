---
id: ADR-0003
title: Pragmatism Charter
status: Accepted
date: 2026-02-04
supersedes: []
superseded_by: []
audience: both
summary: "Umbrella philosophy under which the other ADRs operate: framework coupling is acceptable, no extra layers without justification, Message Bus is preferred but legal escape hatches exist for CRUD/legacy/perf-critical paths."
---

# ADR-0003: Pragmatism Charter

**TL;DR:** This boilerplate explicitly chooses *pragmatic Symfony* over *hexagonal-pure / DDD-pure*. We accept framework coupling as a trade-off for development speed and onboarding ergonomics. Concrete rules (slice layout, CQRS, runtime, testing) live in ADR-0001/0002/0004/0008; this ADR is the philosophy they all assume.

## Context

When building with Symfony, teams oscillate between two failure modes:

1. **Over-engineering** — hexagonal layers, ports, adapters, mappers for every CRUD endpoint. High file count, high onboarding cost, low velocity.
2. **Spaghetti** — controllers full of business logic, no separation of write/read, framework leaks everywhere.

Vertical Slices (ADR-0001) and Symfony Messenger CQRS (ADR-0002) handle the second failure mode. This ADR is the explicit defence against the first — the written record of "we considered hexagonal, we declined, here is why".

Eric Evans warns against fighting your framework. We don't. We use Symfony idioms directly and pay the framework-coupling cost knowingly.

## Decision

The following principles are **binding** and override any ADR-0001/0002/0004 detail in case of conflict:

### 1. Framework coupling is acceptable

We use Symfony attributes (`#[Route]`, `#[Assert]`, `#[OA]`, `#[AsMessageHandler]`, `#[MapRequestPayload]`) directly in business code. We do not extract framework-independent domain layers prophylactically. We pay the framework-migration cost (which is rare) for compounding development-speed gains (which are constant).

### 2. No extra layers without justification

- **No interface for a single implementation.** Add an interface only when there are ≥ 2 implementations or when test substitution genuinely cannot be done with a mocked concrete class.
- **One input contract, one bus message — nothing between.** The `*Request` DTO (`#[MapRequestPayload]` + `#[Assert]`) is the single wire contract; the controller maps it into a pure `*Command` (ADR-0016 §4). No further wrappers, mappers or assemblers around that pair. The `*Command` carries no `#[Assert]` — input validation is the edge's job, invariants the aggregate's (ADR-0018).
- **No repository abstraction for trivial Doctrine queries.** Use `EntityManagerInterface` directly when the call is one-line.
- **The default chain is** Controller → Message Bus → Handler → Entity. Anything else needs a sentence of justification in the PR description.

### 3. Attributes are the source of truth for contracts

Validation, routing and security live as attributes on the class they describe; the OpenAPI spec is generated from those attributes and DTO types (`make open-api`). We do not maintain parallel YAML/XML/JSON config files for the same facts.

### 4. Native Symfony tooling first

- `#[MapRequestPayload]` for JSON-to-DTO conversion.
- Symfony Messenger for CQRS.
- Symfony Validator for input validation.
- Symfony Serializer (or AssetMapper for HTML-first) for output.

If a problem looks solvable with a Symfony component, try the component before introducing a third-party library.

### 5. Message Bus preferred — but with explicit escape hatches

Symfony Messenger is the default for write operations (commands), reads (queries), and cross-feature notification (events) — see ADR-0002.

**Direct service calls are explicitly legal** for these cases:

| Case | Why direct call is acceptable |
|---|---|
| Single-writer CRUD with no async, no events, no cross-feature consumers | The bus indirection adds zero value over `$service->doIt($input)`. |
| Legacy / migration | While migrating from a non-CQRS module, direct calls coexist with newly-written bus dispatches. |
| Performance-critical hot path | When microsecond latency matters and the dispatch envelope is measurable overhead. |

**When the bus is mandatory**: multiple handlers possible, async transport needed, audit-trail needed, cross-feature event consumers, or any future intent to extract to a separate service.

If unsure: dispatch a command. The cost of the bus is small; the cost of refactoring later is large.

## Comparison

| Axis | Pure / Hexagonal (DDD-strict) | Pragmatism Charter |
|---|---|---|
| **Domain code framework-aware?** | Forbidden. Ports + adapters mediate. | Encouraged. `#[AsMessageHandler]` lives on the handler. |
| **Class count per feature** | 8–15 (entity, VO, repo iface, repo impl, port, adapter, mapper, command, handler, dto…) | 3–6 (command, handler, result, controller; optional: domain VO, infra adapter) |
| **Time-to-first-feature** | Days | Hours |
| **Tests** | Pure unit on domain | Unit on handler + e2e via ApiTestCase |
| **Validation lives in** | Domain Value Objects | input on the `*Request` (`#[Assert]`, ADR-0016); invariants in the aggregate / VO (ADR-0018) |
| **API spec lives in** | Separate YAML | generated from routes and DTO types (`make open-api`) |
| **Cost of framework migration** | Low (domain is portable) | High (rewrite). **Accepted trade-off.** |

## Consequences

### Positive

- Faster delivery, lower file count, lower cognitive load on review.
- One source of truth per fact (validation = attribute, not duplicated YAML).
- Junior PHP devs are productive without learning hexagonal vocabulary.
- AI agents generate idiomatic Symfony, not bespoke abstractions.

### Negative & explicit acceptances

| Trade-off | Acceptance |
|---|---|
| Framework migration is expensive | Accepted. Symfony is a long-term commitment. |
| Domain logic is harder to test in isolation when it touches `EntityManagerInterface` directly | Accepted. Use integration tests for those handlers (see ADR-0008). |
| `#[Assert]` + `#[OA]` attributes can clutter a class | Accepted. Single Responsibility means one DTO per case — clutter doesn't compound. |
| Some DDD purists will dislike this | Accepted. They are not the audience. |

## Relationship to other ADRs

- **ADR-0001** (Vertical Slices) — *implements* this charter for code organisation.
- **ADR-0002** (Messenger Transport) — *implements* the bus rule from §5; this ADR's escape hatches override defaults if the case fits.
- **ADR-0004** (FrankenPHP Runtime) — *implements* the "native Symfony tooling first" principle for the runtime layer.
- **ADR-0008** (Testing Strategy) — *operationalises* the testing trade-off in §Negative.

This ADR is the umbrella; all others assume it.

## References

- Evans, *Domain-Driven Design*, on framework integration: "Don't fight your framework."
- Symfony [Best Practices](https://symfony.com/doc/current/best_practices.html).
