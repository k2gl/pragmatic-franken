---
audience: agent,human
tier: 1
budget_tokens: 2000
last_reviewed: 2026-06-12
---

# AGENTS.md — Pragmatic FrankenPHP

PHP 8.5 / Symfony 8 / FrankenPHP boilerplate. Vertical Slices + CQRS over Messenger, worker mode. This file is the **only** AI-default context — everything else loads on demand from `docs/`.

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

`{Context}` = a DDD Bounded Context (`User`, `Task`, `Health`…) — its own ubiquitous language and consistency boundary (ADR-0001).

```
src/
  Kernel.php                            # App\Kernel
  SharedKernel/                         # cross-context infra: repo base, problem+json listeners (ADR-0009)
  Context/{Name}/
    Entity/                             # Doctrine entities of the context
    Repository/                         # typed repositories (extend SharedKernel DoctrineRepository)
    Features/{Feature}/
      Domain/                           # value objects, domain events (optional)
      Application/                      # Handler at root; Message/ (*Command|*Query), Dto/ (*Result)
      Infrastructure/                   # adapters: HTTP clients, gateways, etc.
      EntryPoint/Http/{Feature}Controller.php
      EntryPoint/Cli/{Verb}{Feature}CliCommand.php   # if a CLI entry exists
config/  bin/  public/  assets/         # standard Symfony layout
docs/    dev/  ops/  migrations/        # ADRs, codegen, deploy, Doctrine migrations
tests/   Context/{Name}/Features/{Feature}/   # mirror of src/
```

## Hard rules (DO / DO-NOT)

- **DO** put feature code in `src/Context/{Name}/Features/{Feature}/`; entities in `Entity/`, repositories in `Repository/` of the context. See ADR-0001.
- **DO** name CQRS commands `Create{Feature}Command`, `Update{Feature}Command`, etc. — verb + noun.
- **DO** use `#[AsMessageHandler]` on handlers and dispatch via `MessageBusInterface`.
- **DO** name Symfony Console classes `*CliCommand extends Command` in `EntryPoint/Cli/`.
- **DO** use attributes for routing, validation, Doctrine mapping. No XML/YAML for those.
- **DO** keep controllers slim: parse input → dispatch command/query → return result.
- **DO** build aggregates via a named constructor (`Task::create()`, private `__construct`) that guards invariants; keep `*Command` pure — `#[Assert]` goes on the `*Request`, not the command. ADR-0018.
- **DO-NOT** create global `Controllers/`, `Services/`, `Repositories/` directories.
- **DO-NOT** create an interface unless ≥ 2 implementations or test substitution is unavoidable.
- **DO-NOT** hold mutable static state — FrankenPHP worker mode reuses the kernel between requests. See ADR-0004.
- **DO-NOT** return Doctrine entities from query handlers or controllers — implicit lazy loads; return DTOs.
- **DO-NOT** add files anywhere outside the slice for slice-scoped logic. Deletion of the slice folder must leave nothing dangling.
- **DO** replace scaffold placeholders before committing — `make slice` writes a stub.

## Slice anatomy (canonical, ADR-0001)

```
src/Context/User/Features/Register/
  Application/RegisterHandler.php
  Application/Message/RegisterCommand.php
  Application/Dto/RegisterRequest.php     # HTTP input + #[Assert] (payload entries)
  Application/Dto/RegisterResult.php
  Infrastructure/PasswordHasher.php
  EntryPoint/Http/RegisterController.php   # POST /user/register
  EntryPoint/Cli/RegisterUserCliCommand.php  # bin/console user:register (optional)
```

`Domain/` is added only when the feature owns value objects or domain events. Cross-feature code goes up to `src/Context/{Name}/Shared/` after the Rule of Three; cross-context infra to `src/SharedKernel/`. See ADR-0009.

## Naming cheat-sheet

| What | Class name | Location |
|---|---|---|
| CQRS command (write) | `Create{Feature}Command` | `Application/Message/` |
| CQRS query (read) | `Get{Feature}Query` | `Application/Message/` |
| Handler / event subscriber | `{Feature}Handler`, `On{Event}{Reaction}` | `Application/` |
| Result / other DTO | `{Feature}Result` | `Application/Dto/` |
| HTTP controller | `{Feature}Controller` | `EntryPoint/Http/` |
| Symfony Console | `{Verb}{Feature}CliCommand` extends `Command` | `EntryPoint/Cli/` |
| Domain event | `{Feature}{PastTenseVerb}` | `Domain/` or `src/Context/{Name}/Shared/Events/` |

## Runtime mode

FrankenPHP worker mode keeps the Symfony kernel hot between requests. Stateless handlers only. Worker command comes from the `FRANKENPHP_CONFIG` env (compose / Dockerfile); PHP limits live in `docker/php/*.ini`. Health probes at `/healthz` (liveness) and `/ready` (readiness). See ADR-0004, ADR-0005, `docs/guides/worker-mode.md`.

## Testing

Pyramid 60 / 30 / 10 (unit / integration / e2e). CI enforces a global 60 % statement-coverage floor; per-layer targets (Domain ≥ 90 % …) are fork policy — see ADR-0008. Layout mirrors `src/` at `tests/Context/{Name}/Features/{Feature}/`. PHPUnit 12 + Zenstruck (Foundry, Browser, Messenger-Test) + DAMA. See `docs/guides/testing.md`.

## Pointer index

| Doc | Load when |
|---|---|
| `docs/adr/0001-vertical-slices.md` | slice layout, shared folders |
| `docs/adr/0002-messenger-transport.md` | async flows, transports |
| `docs/adr/0003-pragmatic-symfony-architecture.md` | extra abstraction; skipping the Message Bus |
| `docs/adr/0004-frankenphp-runtime.md` | worker behavior, env tuning, deploy |
| `docs/adr/0005-health-checks.md` | probes, `/healthz`, `/ready` |
| `docs/adr/0006-memory-management.md` | OOM, GC, OPcache tuning |
| `docs/adr/0007-asset-mapper.md` | frontend assets / SPA decision |
| `docs/adr/0008-testing-strategy.md` | tests, CI gates |
| `docs/adr/0009-shared-architecture.md` | Rule-of-Three / extracting Shared |
| `docs/adr/0010-documentation-and-ai-layout.md` | adding docs, editing AGENTS.md |
| `docs/adr/0011-event-sourcing-lite.md` | domain events, async side effects |
| `docs/adr/0012-ubiquitous-language.md` | naming, entity placement |
| `docs/adr/0013-doctrine-repository-pattern.md` | persistence, writing repositories |
| `docs/adr/0014-supply-chain-security.md` | attestations, verifying images/artifacts |
| `docs/adr/0015-scheduler-and-periodic-tasks.md` | recurring/cron work |
| `docs/adr/0016-http-response-contract.md` | response shape, `data` envelope |
| `docs/adr/0017-parallel-agent-sessions.md` | parallel sessions, worktree forks |
| `docs/guides/supply-chain.md` | sign/verify how-to, deploy gate |
| `docs/guides/development.md` | day-to-day commands, scaffolding details |
| `docs/guides/testing.md` | concrete testing examples |
| `docs/guides/worker-mode.md` | debugging FrankenPHP worker behavior |
| `docs/guides/mercure-integration.md` | real-time SSE via Mercure |
| `docs/guides/sdk-generation.md` | generating TypeScript types from Result DTOs |
| `docs/guides/deployment.md` | deploying to a VDS, rollout, proxy topology |
| `docs/guides/disaster-recovery.md` | backups, restore drill |
| `docs/guides/parallel-sessions.md` | worktree forks how-to |

ADR-0003 is the umbrella *Pragmatism Charter* — load it before adding any extra layer/interface, or to skip the Message Bus for a CRUD case. Optional capability recipes live in `docs/recipes/`.

## Forbidden patterns (agent-targeted)

- Don't invent folders not listed in the Directory map.
- Don't generate an interface for a class that has one implementation.
- Don't put CLI command classes in a global `Command/` namespace — use `EntryPoint/Cli/`.
- Don't introduce new top-level dirs (`scripts/`, `tools/`, `internal/`) — codegen → `dev/`, deploy → `ops/`.
- Don't write per-IDE rule files (`.cursorrules`, `.windsurfrules`, `.cursor/rules/*`). This file is the only place.
