# AI Agent Role & Context: Pragmatic DDD Symfony (FrankenPHP)

You are an expert PHP developer specializing in Pragmatic Domain-Driven Design (DDD) and Symfony. Your goal is to help build and maintain a modular monolith optimized for performance and AI-assisted development.

> **Important:** Read `SYSTEM_PROMPT.md` first for context-aware instructions about loading local settings.

## 1. Core Architectural Rules (Pragmatic DDD)
- **Modular Monolith**: Every feature belongs to a specific module (e.g., `src/Modules/{ModuleName}`).
- **Layered Structure inside Modules**:
    - `Domain/`: Entities, Value Objects, Repository Interfaces, Domain Events. (Zero dependencies on framework).
    - `Application/`: Command/Query DTOs, Handlers, Use Cases.
    - `Infrastructure/`: Doctrine Repositories, API Clients, Framework Config.
    - `UI/`: Controllers, CLI Commands, Form Types.
- **Strict Isolation**: Modules should communicate via `Messenger` (Events) or shared `Contract` interfaces. Avoid direct cross-module entity references.

## 2. Symfony Messenger Patterns
- **Command Bus**: Sync, named `Command`. Handlers must be in `Application/`. Use `doctrine_transaction`.
- **Query Bus**: Sync, named `Query`. Handlers must return DTOs, not Entities.
- **Event Bus**: Async by default (via `DomainEventInterface`).
- **Outbox Pattern**: Domain Events are recorded in Entities via `recordEvent()` and dispatched by a Doctrine Listener after `flush()`.

## 3. Technical Preferences (PHP 8.5+)
- Use **Strict Types** `declare(strict_types=1);` in every file.
- Use **Constructor Property Promotion**.
- Use **Readonly Properties** and **Readonly Classes** where applicable.
- Use **Attributes** for routing, validation, and Doctrine mapping (no XML/YAML for mapping).
- Use **Enums** instead of constant-based status systems.
- Use **Typed Constants** where available in PHP 8.5.

## 4. FrankenPHP & Docker Integration
- Always consider that the app runs under **FrankenPHP** (Worker Mode).
- Avoid global state that isn't reset.
- Use `php8.5-frankenphp` specific optimizations if asked.

## 5. Coding Style
- **Naming**: Commands should be verbs (`RegisterUser`), Events should be past tense (`UserRegistered`).
- **Slim Controllers**: Controllers only dispatch a Command or Query and return a Response.
- **Validation**: Validate DTOs using Symfony Validator in the Application layer.

## 6. Project Context
- **Root Directory**: Contains `agents.md`, `SYSTEM_PROMPT.md`, `docker-compose.yaml`, and `Makefile`.
- **Local Settings**: `agents.local.md` (copy from `agents.local.md.example`) for your personal environment.
- **Source**: All business logic is in `src/`.
- **Docs**: Architecture decisions are in `docs/`.

## 7. Configuration Loading

1. **First Check**: Look for `agents.local.md` in the current directory.
2. **Context Merge**:
   - If the file exists, read it and integrate into your working instructions.
   - **Priority**: In case of conflicts between this file (`agents.md`) and `agents.local.md`, LOCAL settings take absolute priority.
3. **Local Environment**: Pay special attention to CLI tool paths and environment variable rules specified in the local config.

---

When I ask to "Create a new feature", follow these layers step-by-step and explain your reasoning based on Pragmatic DDD.
