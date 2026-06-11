---
audience: agent,human
tier: 1
budget_tokens: 2000
last_reviewed: 2026-04-28
---

# AGENTS.md — Pragmatic Franken

PHP 8.5 / Symfony 8 / FrankenPHP boilerplate. Architecture: Vertical Slices + CQRS over Symfony Messenger, FrankenPHP worker mode. This file is the **only** AI-default context — everything else loads on demand from `docs/`.

## Quickstart

```bash
make install     # env, containers, deps, migrations
make up          # start containers
make smoke       # bin/console list && curl /healthz
make test        # PHPUnit
make ci          # lint-check + analyze + test (matches CI)
make slice context=Foo feature=Bar  # scaffold a new slice
```

## Directory map

`{Context}` = a DDD Bounded Context (`User`, `Billing`, `Health`…). Each has its own ubiquitous language and consistency boundary, not just a code module. See ADR-0001.

```
src/
  Kernel.php                            # App\Kernel
  Context/{Name}/
    Entity/                             # Doctrine entities of the context
    Repository/                         # typed repositories (extend SharedKernel DoctrineRepository)
    Features/{Feature}/
      Domain/                           # value objects, domain events (optional)
      Application/                      # *Command / *Query / *Handler / *Result
      Infrastructure/                   # adapters: HTTP clients, gateways, etc.
      EntryPoint/Http/{Feature}Controller.php
      EntryPoint/Cli/{Verb}{Feature}CliCommand.php   # if a CLI entry exists
config/  bin/  public/  assets/         # standard Symfony layout
docs/    dev/  ops/  migrations/        # ADRs, codegen, deploy, Doctrine migrations
tests/   Context/{Name}/Features/{Feature}/   # mirror of src/
```

## Hard rules (DO / DO-NOT)

- **DO** put feature code in `src/Context/{Name}/Features/{Feature}/`; entities in `src/Context/{Name}/Entity/`, repositories in `Repository/`. See ADR-0001.
- **DO** name CQRS commands `Create{Feature}Command`, `Update{Feature}Command`, etc. — verb + noun.
- **DO** use `#[AsMessageHandler]` on handlers and dispatch via `MessageBusInterface`.
- **DO** name Symfony Console classes `*CliCommand extends Command` in `EntryPoint/Cli/`.
- **DO** use attributes for routing, validation, Doctrine mapping. No XML/YAML for those.
- **DO** keep controllers slim: parse input → dispatch command/query → return result.
- **DO-NOT** create global `Controllers/`, `Services/`, `Repositories/` directories.
- **DO-NOT** create an interface unless ≥ 2 implementations or test substitution is unavoidable.
- **DO-NOT** hold mutable static state — FrankenPHP worker mode reuses the kernel between requests. See ADR-0004.
- **DO-NOT** return Doctrine entities from query handlers or controllers — return DTOs.
- **DO-NOT** add files anywhere outside the slice for slice-scoped logic. Deletion of the slice folder must leave nothing dangling.

## Slice anatomy (canonical, ADR-0001)

```
src/Context/User/Features/Register/
  Application/RegisterCommand.php
  Application/RegisterHandler.php
  Application/RegisterResult.php
  Infrastructure/PasswordHasher.php
  EntryPoint/Http/RegisterController.php   # POST /user/register
  EntryPoint/Cli/RegisterUserCliCommand.php  # bin/console user:register (optional)
```

`Domain/` is added only when the feature has its own value objects or domain events. Cross-feature shared code lives at the next level up (`src/Context/{Name}/Shared/`) only after the Rule of Three (used in 3+ slices); cross-context infrastructure goes to `src/SharedKernel/`. See ADR-0009.

## Naming cheat-sheet

| What | Class name | Location |
|---|---|---|
| CQRS command (write) | `Create{Feature}Command` | `Application/` |
| CQRS query (read) | `Get{Feature}Query` | `Application/` |
| Handler | `{Feature}Handler` | `Application/` |
| Result DTO | `{Feature}Result` | `Application/` |
| HTTP controller | `{Feature}Controller` | `EntryPoint/Http/` |
| Symfony Console | `{Verb}{Feature}CliCommand` extends `Command` | `EntryPoint/Cli/` |
| Domain event | `{Feature}{PastTenseVerb}` | `Domain/` (intra-feature) or `src/Context/{Name}/Shared/Events/` (cross-context) |

## Runtime mode

FrankenPHP worker mode keeps the Symfony kernel hot between requests. Stateless handlers only. Worker command comes from the `FRANKENPHP_CONFIG` env (compose / Dockerfile); PHP limits live in `docker/php/*.ini`. Health probes at `/healthz` (liveness) and `/ready` (readiness). See ADR-0004, ADR-0005, `docs/guides/worker-mode.md`.

## Testing

Pyramid 60 / 30 / 10 (unit / integration / e2e). Recommended coverage targets (fork policy, not a CI gate): Domain ≥ 90 %, Application ≥ 80 %, Infrastructure ≥ 60 %, UI ≥ 40 %. Layout mirrors `src/` at `tests/Context/{Name}/Features/{Feature}/`. PHPUnit 11 + Zenstruck (Foundry, Browser, Messenger-Test) + DAMA. See ADR-0008, `docs/guides/testing.md`.

## Pitfalls

- Static state in worker mode → request leakage. Reset or avoid.
- Copy-paste between slices is fine until ≥ 3 occurrences (Rule of Three). Then extract.
- Returning entities through HTTP makes implicit DB queries during serialization; always pass through a DTO.
- `make slice` writes a stub — replace placeholders before committing.

## Local overrides

Per-developer settings live in `AGENTS.local.md` (gitignored) — copy `AGENTS.local.md.example`. Never override architectural rules there; only personal preferences (tone, language, paths).

## Pointer index

| Doc | Load when |
|---|---|
| `docs/adr/0001-vertical-slices.md` | adding/refactoring a slice or shared folder |
| `docs/adr/0002-messenger-transport.md` | designing async flows, choosing transport |
| `docs/adr/0003-pragmatic-symfony-architecture.md` | weighing extra abstraction; deciding when to skip the Message Bus |
| `docs/adr/0004-frankenphp-runtime.md` | worker behavior, env tuning, deploy |
| `docs/adr/0005-health-checks.md` | adding probes or modifying `/healthz` |
| `docs/adr/0006-memory-management.md` | OOM, GC, OPcache tuning |
| `docs/adr/0007-asset-mapper.md` | frontend assets / SPA decision |
| `docs/adr/0008-testing-strategy.md` | writing tests or CI gates |
| `docs/adr/0009-shared-architecture.md` | Rule-of-Three / extracting Shared |
| `docs/adr/0010-documentation-and-ai-layout.md` | adding docs, editing AGENTS.md |
| `docs/adr/0011-event-sourcing-lite.md` | domain events, async side effects, event-driven decoupling |
| `docs/guides/development.md` | day-to-day commands, scaffolding details |
| `docs/guides/testing.md` | concrete testing examples |
| `docs/guides/worker-mode.md` | debugging FrankenPHP worker behavior |
| `docs/guides/mercure-integration.md` | real-time SSE via Mercure; publishing and subscribing |
| `docs/guides/sdk-generation.md` | generating TypeScript types from Result DTOs |

ADR-0003 is the umbrella *Pragmatism Charter* — load it whenever you're tempted to add an extra layer or interface, or when deciding whether the Message Bus is overkill for a CRUD case.

## Forbidden patterns (agent-targeted)

- Don't invent folders not listed in the Directory map.
- Don't generate an interface for a class that has one implementation.
- Don't put CLI command classes in a global `Command/` namespace — use `EntryPoint/Cli/`.
- Don't introduce new top-level dirs (`scripts/`, `tools/`, `internal/`) — codegen → `dev/`, deploy → `ops/`.
- Don't write per-IDE rule files (`.cursorrules`, `.windsurfrules`, `.cursor/rules/*`). This file is the only place.
