---
id: ADR-0018
title: Input Validation & Domain Invariants
status: Accepted
date: 2026-06-13
supersedes: []
superseded_by: []
audience: both
summary: "Two layers, never one. Input is validated at every edge (`#[Assert]` on the `*Request` for HTTP → 422); invariants are enforced in the core — aggregates are built through a named constructor with a non-public `__construct`, and a value with a real rule becomes a Value Object that throws in its constructor. The `*Command` stays a pure message (no `#[Assert]`); an aggregate-less write guards in the command constructor. Closes the gap where a rule lived only behind HTTP while CLI/seeder/queue paths could create invalid state."
---

# ADR-0018: Input Validation & Domain Invariants

**TL;DR:** Validate input at every **edge**; enforce invariants in the **core**. The HTTP edge keeps `#[Assert]` on the `*Request` DTO (→ 422 + violations). The core makes invalid state unconstructable: an aggregate is created only through a named constructor (`Task::create()`) with a non-public `__construct`, and a value with a real rule becomes a Value Object that throws in its constructor. The `*Command` is a pure transport message — never `#[Assert]`. A write with no aggregate guards itself in the command constructor.

## Context

Before this ADR the only rule about validation (ADR-0003) read *"validation lives in `#[Assert]` on the Request DTO"*. Taken literally that put the `CreateTask` invariant **only** on the HTTP path: `Task`'s constructor checked nothing, the message bus does not validate messages, and `app:seed` built `new Task($title)` straight past the edge. A column `length:` is not a guard either — it is DDL, enforced (if at all) by the database as a 500, not as a domain rule.

The defect is conflating two different things under one word:

- **Input validation** — boundary, per-channel, ergonomic. *"Is this request well-formed?"* Produces a 422 + violation list for HTTP, an exit code for CLI. It is *expected* to be duplicated per entry point.
- **Invariant** — always-true, channel-independent. *"A task always has a non-blank title."* Must hold no matter who constructs the model.

They legitimately overlap; having both is defence-in-depth, not a violated "single source of truth" (that rule is about not keeping parallel YAML + attributes for the *same* boundary fact).

## Decision

### 1. Validate input at every edge

Each entry point owns and validates its input contract:

- **HTTP** → `#[Assert]` on the `*Request` DTO via `#[MapRequestPayload]` → 422 problem+json (ADR-0016 §4).
- **CLI** → console `InputArgument`/`InputOption` (+ the Validator when non-trivial).
- **Queue / Scheduler** → the message shape.

### 2. Enforce invariants in the core

A write must be unable to produce invalid state on any channel:

- **Slice with an aggregate** — the aggregate guards its invariants in its **constructor** (creation) and **mutators** (transitions). Construction goes through a **named constructor** (`Task::create()`, `User::register()` / `registerAsAgent()`) and the `__construct` is **non-public**, so `new Aggregate()` cannot be called from outside. Doctrine hydration uses `newInstanceWithoutConstructor()`, so a private constructor is invisible to the ORM and the guard never fires on load.
- **Slice without an aggregate** — the `*Command` is self-validating: it guards in its constructor, an imperative check (e.g. `PublishLiveUpdateCommand` rejecting a blank `topic`).

### 3. Promote a value to a Value Object when it has rules

A value with ≥ 1 real rule (non-blank, length, format, range) or shared across slices becomes a Value Object that throws in its constructor — always-valid. Shipped reference: `Task\Shared\TaskTitle`, mapped onto its existing column by a `task_title` Doctrine type (no schema change). Do **not** create a VO for ruleless primitives (opaque ids, flags) — Rule of Three (ADR-0009).

### 4. The `*Command` is a pure message

No `#[Assert]`, no HTTP attributes. A command may be serialized onto async transport and may have several producers (e.g. `PublishLiveUpdateCommand` is built by the HTTP controller **and** by an event subscriber). Validation attributes are an edge concern; keeping the command pure keeps the public wire contract and the internal transport message independently versionable.

### 5. Single-source shared numbers

A limit shared by edge and core is one constant on the value object / aggregate (`TaskTitle::MAX_LENGTH`), referenced by `#[ORM\Column(length:)]`, `#[Assert\Length(max:)]`, and the guard.

## Comparison

| Path | Boundary-only (old) | Edge + core (this ADR) |
|---|---|---|
| HTTP create | 422 via `#[Assert]` | 422 via `#[Assert]` (unchanged) |
| CLI / seeder / queue / test | **unguarded** | guarded by the aggregate / command |
| `new Task('')` | succeeds | impossible (private constructor) |
| Invariant unit-testable | only via e2e HTTP | fast unit test on the aggregate |

## Consequences

**Positive:** invalid state is unconstructable on every path; invariants get fast unit tests (pyramid, ADR-0008); the rule has one home; intent is explicit (`Task::create()` vs `new Task`).

**Negative & accepted:** a named constructor + private `__construct` is slightly more ceremony than a public constructor (test factories build via the named constructor — Foundry `Instantiator::namedConstructor()`); a Value Object adds a class and, when persisted, a Doctrine type. Accepted only where a value has a real rule — not blanket VO-per-primitive (the ADR-0003 anti-over-engineering stance holds).

## Relationship to other ADRs

- **ADR-0003** (Pragmatism Charter) — refines its "validation lives in the Request DTO" row into this two-layer rule; it does not license extra layers.
- **ADR-0016** (HTTP Response Contract) — the edge half (`*Request` → 422) is unchanged.
- **ADR-0012** (Ubiquitous Language & Entity Placement) — aggregates are created via named constructors.
- **ADR-0009** (Shared Architecture) — Value Objects by Rule of Three; `TaskTitle` lives in `Task/Shared/`.

## References

- `src/Context/Task/Entity/Task.php`, `src/Context/Task/Shared/TaskTitle.php` — reference implementation.
- Evans, *Domain-Driven Design* (Factories, Value Objects); Vernon, *Implementing DDD* (always-valid aggregates); Noback, "Named constructors".
