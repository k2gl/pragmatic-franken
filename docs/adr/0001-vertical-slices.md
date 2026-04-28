---
id: ADR-0001
title: Vertical Slices
status: Accepted
date: 2026-02-06
supersedes: []
superseded_by: []
audience: both
summary: "Group code by business value, not technical type. Each feature is a self-contained directory at src/{Module}/Features/{Feature}/ with internal Domain/Application/Infrastructure/EntryPoint sub-folders."
---

# ADR-0001: Vertical Slices

**TL;DR:** Every business action is one folder under `src/{Module}/Features/{Feature}/`. Inside the slice, code is organised by DDD-style layer (`Domain/`, `Application/`, `Infrastructure/`, `EntryPoint/`). Cross-feature reuse is handled by ADR-0009 (Shared Architecture). Architecture is optimised for **deletion**, not reusability.

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

Each business action is a self-contained directory:

```
src/{Module}/Features/{Feature}/
```

### 2. Canonical slice layout

```
src/User/Features/Register/
├── Domain/                          # value objects, domain events, entities (optional)
├── Application/
│   ├── RegisterCommand.php          # CQRS command (verb + noun)
│   ├── RegisterHandler.php          # #[AsMessageHandler]
│   └── RegisterResult.php           # output DTO
├── Infrastructure/                  # adapters: Doctrine repos, HTTP clients, ...
└── EntryPoint/
    ├── Http/RegisterController.php  # POST /user/register
    └── Cli/RegisterUserCliCommand.php   # bin/console user:register (optional)
```

Rules:

- `Domain/` is **optional**. Only create it when the feature actually owns a value object, domain event, or entity that is not shared with another feature.
- `Infrastructure/` is **optional**. Only create it when the feature owns adapters (repository implementation, third-party client). Pure Application slices may omit it.
- Each EntryPoint has its own subdirectory (`Http/`, `Cli/`, `Queue/`). One feature can have multiple entry points.
- A feature folder must be deletable in one `rm -rf` without leaving dangling references anywhere else.

### 3. Naming

- CQRS commands: `Create{Feature}Command`, `Update{Feature}Command`, `Delete{Feature}Command` — verb + noun.
- CQRS queries: `Get{Feature}Query`, `List{Feature}Query`, `Find{Feature}Query`.
- Handlers: `{Feature}Handler` with `#[AsMessageHandler]`.
- HTTP entry: `{Feature}Controller` in `EntryPoint/Http/`.
- CLI entry: `{Verb}{Feature}CliCommand` extends `Symfony\Component\Console\Command\Command` in `EntryPoint/Cli/`. The `CliCommand` suffix exists to avoid collision with CQRS `*Command` classes; namespace separation handles the rest.

### 4. Cross-feature communication

- Features in the same module **must not** depend on each other directly. If they need to coordinate, dispatch an event.
- Truly common code goes in `src/{Module}/Shared/` (Rule of Three — see ADR-0009).
- Inter-module events live in `src/{Module}/Shared/Events/` (e.g., `src/User/Shared/Events/UserRegisteredEvent.php`).

### 5. Cron jobs

A cron job is a trigger, not a feature. The work it performs is a feature; place it under `Features/`:

```
src/User/Features/CleanInactiveAccounts/
├── Application/CleanInactiveAccountsCommand.php
├── Application/CleanInactiveAccountsHandler.php
└── EntryPoint/Cli/CleanInactiveAccountsCliCommand.php
```

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

1. New features must be scaffolded with `make slice module=Foo feature=Bar`.
2. PRs introducing global directories (`Controllers/`, `Services/`, `Repositories/`) at module root are rejected.
3. PRs introducing top-level layout drift (renamed `Features/`, `EntryPoint/`) are rejected.

## Relationship to other ADRs

- **ADR-0002** — Messenger Transport: defines the bus that Application handlers dispatch to.
- **ADR-0008** — Testing Strategy: tests mirror this layout at `tests/{Module}/Features/{Feature}/`.
- **ADR-0009** — Shared Architecture: defines what lives at `src/Shared/` and `src/{Module}/Shared/`.
- **ADR-0003** — Pragmatic Symfony Architecture: superseded by this ADR + ADR-0002 + ADR-0004.
