---
id: ADR-0001
title: Vertical Slices
status: Accepted
date: 2026-02-06
supersedes: []
superseded_by: []
audience: both
summary: "Group code by business value, not technical type. Each feature is a self-contained directory at src/Context/{Context}/Features/{Feature}/ with internal Domain/Application/Infrastructure/EntryPoint sub-folders; inside Application/ the handler sits at the root, messages in Message/, DTOs in Dto/. {Context} is a DDD Bounded Context."
---

# ADR-0001: Vertical Slices

**TL;DR:** Every business action is one folder under `src/Context/{Context}/Features/{Feature}/`. Inside the slice, code is organised by DDD-style layer (`Domain/`, `Application/`, `Infrastructure/`, `EntryPoint/`). Cross-feature reuse is handled by ADR-0009 (Shared Architecture). Architecture is optimised for **deletion**, not reusability.

## Context

Traditional layered architecture (Controllers → Services → Entities) leads to **shotgun surgery**: a single business change touches files in 5+ unrelated directories. This raises cognitive load, fragments AI context, and makes feature deletion nearly impossible.

| Scenario | Layered | Vertical Slices |
|---|---|---|
| Add a field | edit Entity, DTO, Request, Response, Repository, Service | edit one folder |
| Find related code | search 5+ directories | one folder |
| Remove feature | delete files in 5+ places | delete one folder |
| Onboarding | 2–4 weeks | 1–2 days |

## Decision

### 1. Slice location

Each business action is a self-contained directory inside a Bounded Context:

```
src/Context/{Context}/Features/{Feature}/
```

The context root also holds the context-wide pieces: `Entity/` and `Repository/` (ADR-0012, ADR-0013), and — when the project grows them — `Shared/` (Rule of Three, ADR-0009), `Reference/` (context enums/constants) and `Security/` (voters, role logic).

`{Context}` is a **DDD Bounded Context** (Eric Evans) — its own ubiquitous language, model and consistency boundary. `User`, `Task`, `Health` are contexts; `User.Login` is a slice within `User`. We say `{Context}`, not `{Module}`: the boundary is strategic, not just a namespace.

### 2. Canonical slice layout

```
src/Context/User/Features/Register/
├── Domain/                              # value objects, domain events (optional)
├── Application/
│   ├── RegisterHandler.php              # #[AsMessageHandler]; subscribers (On{Event}{Reaction}) live here too
│   ├── Message/
│   │   └── RegisterCommand.php          # CQRS command (verb + noun)
│   └── Dto/
│       ├── RegisterRequest.php          # HTTP input contract (#[MapRequestPayload] + validation) — only when the entry point takes a payload
│       └── RegisterResult.php           # output DTO
├── Infrastructure/                      # adapters: HTTP clients, gateways, ... (optional)
└── EntryPoint/
    ├── Http/RegisterController.php      # POST /user/register
    └── Cli/RegisterUserCliCommand.php   # bin/console user:register (optional)
```

Rules:

- Inside `Application/` only the `*Handler` (and event subscribers) sit at the root. Dispatched messages (`*Command`, `*Query`) go to `Message/`; `*Request`, `*Result` and other DTOs go to `Dto/`.
- Entities and repositories are **not** slice property — they live at the context root (`Entity/`, `Repository/`; ADR-0012).
- `Domain/` is **optional**. Only create it when the feature actually owns a value object or domain event that is not shared with another feature.
- `Infrastructure/` is **optional**. Only create it when the feature owns adapters (repository implementation, third-party client). Pure Application slices may omit it.
- Each EntryPoint has its own subdirectory (`Http/`, `Cli/`, `Queue/`). One feature can have multiple entry points.
- A feature folder must be deletable in one `rm -rf` without leaving dangling references anywhere else.

### 3. Naming

- CQRS commands: `Create{Feature}Command`, `Update{Feature}Command`, `Delete{Feature}Command` — verb + noun, in `Application/Message/`.
- CQRS queries: `Get{Feature}Query`, `List{Feature}Query`, `Find{Feature}Query` — in `Application/Message/`.
- Handlers: `{Feature}Handler` with `#[AsMessageHandler]`, at the `Application/` root.
- Input/output DTOs: `{Feature}Request`, `{Feature}Result` — in `Application/Dto/`; success responses wrap the Result in the `data` envelope (ADR-0016).
- HTTP entry: `{Feature}Controller` in `EntryPoint/Http/`.
- CLI entry: `{Verb}{Feature}CliCommand` extends `Symfony\Component\Console\Command\Command` in `EntryPoint/Cli/`. The `CliCommand` suffix exists to avoid collision with CQRS `*Command` classes; namespace separation handles the rest.

### 4. Cross-feature communication

- Features in the same context **must not** depend on each other directly. If they need to coordinate, dispatch an event.
- Truly common code goes in `src/Context/{Context}/Shared/` (Rule of Three — see ADR-0009).
- Cross-context events live in `src/Context/{Context}/Shared/Events/` (e.g., `src/Context/User/Shared/Events/UserRegistered.php`) — past tense, no `Event` suffix (ADR-0011).

### 5. Cron jobs

A cron job is a trigger, not a feature. The work it performs is an ordinary slice scheduled with `#[AsPeriodicTask]` (symfony/scheduler — ADR-0015); shipped example: `src/Context/Task/Features/PurgeCompletedTasks/`. Add an `EntryPoint/Cli/` command only when the same work also needs manual runs.

## Consequences

### Positive

- **High cohesion** — related code is colocated.
- **AI-native** — agents read one folder, not five.
- **Deletion-safe** — `rm -rf` is the safe refactor.
- **Onboarding** — a new dev understands one feature by reading one folder.

### Negative & mitigations

| Risk | Mitigation |
|---|---|
| Code duplication across slices | Acceptable until Rule of Three (ADR-0009). |
| No global overview | Use grep / IDE structure view. |
| Multiple entry points confusion | Subfolders under `EntryPoint/` (`Http/`, `Cli/`, `Queue/`). |

## Compliance

1. New features must be scaffolded with `make slice context=Foo feature=Bar`.
2. PRs introducing global directories (`Controllers/`, `Services/`, `Repositories/`) at module root are rejected.
3. PRs introducing top-level layout drift (renamed `Features/`, `EntryPoint/`) are rejected.

## Relationship to other ADRs

- **ADR-0002** — Messenger Transport: defines the bus that Application handlers dispatch to.
- **ADR-0008** — Testing Strategy: tests mirror this layout at `tests/Context/{Context}/Features/{Feature}/`.
- **ADR-0009** — Shared Architecture: defines what lives at `src/SharedKernel/` and `src/Context/{Context}/Shared/`.
- **ADR-0003** — Pragmatism Charter: the umbrella philosophy under which this ADR (and 0002, 0004) operate.
