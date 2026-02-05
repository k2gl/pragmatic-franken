# ADR 1: Vertical Slices Architecture

## Status

**Accepted**

## Context

Traditional layered architecture (Controllers → Services → Entities) leads to **"Shotgun Surgery"** — where a single business change requires touching files in multiple decoupled directories. This increases cognitive load, complicates AI context, and makes feature deletion nearly impossible.

### The Problem

| Scenario | Layered Architecture | Vertical Slices |
|----------|----------------------|-----------------|
| Add new field | Edit Entity, DTO, Request, Response, Repository, Service | Edit one folder |
| Find related code | Search 5+ directories | One folder |
| Remove feature | Delete files in 5+ places | Delete one folder |
| Onboarding time | 2-4 weeks | 1-2 days |

## Decision

We adopt **Vertical Slices** as the primary organizational principle. We group code by **Business Value**, not by technical type.

### 1. The Feature Folder

Each business action is a self-contained directory:

```
src/[Module]/Features/[FeatureName]/
```

### 2. Flat Structure & Entry Points

A feature folder contains everything it needs to function. We allow **multiple entry points** for the same business logic:

```
src/User/Features/RegisterUser/
├── RegisterUserAction.php    # HTTP entry point (Controller)
├── RegisterUserCommand.php   # CLI entry point (Symfony Console)
├── RegisterUserHandler.php   # Core business logic
├── RegisterUserDto.php       # Input/Output data contracts
└── RegisterUserHandlerTest.php # Feature-specific tests
```

**Note:** No sub-folders like `Input/`, `Output/`, `Handler/` are allowed inside a Feature folder. Keep it flat.

### 3. Rule of Deletion

The architecture is optimized for **deletion**, not for reusability.

To remove a feature, simply delete its folder. If deletion leaves "broken" code elsewhere, the isolation was violated.

```bash
# Complete feature removal
rm -rf src/User/Features/Login/

# No tails left behind
# - No LoginController in controllers/
# - No LoginService in services/
# - No LoginRepository in repositories/
# - No LoginTest in tests/
```

### 4. Cross-Feature Communication

- **Features within the same module:** Should NOT depend on each other directly.
- **Truly common code:** Use `Shared/` within the module or global `src/Shared/`.
- **Inter-module events:** Place in `src/[Module]/Shared/Events/`.

> **See [ADR-0009](0009-shared-architecture.md) for detailed Shared architecture rules.**

### 5. Events

**Intra-feature events:** If an event is used only by this feature, keep it inside the feature folder.

**Inter-module events:** If `User` registration triggers `Notifier`, the event lives in `src/User/Shared/Events/`:

```php
// src/User/Shared/Events/UserRegisteredEvent.php
final readonly class UserRegisteredEvent
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {}
}
```

### 6. Cron Jobs

A cron job is just a trigger. If it performs module-specific action, create a Feature for it:

```
src/User/Features/CleanInactiveAccounts/
├── CleanInactiveAccountsCommand.php
└── CleanInactiveAccountsHandler.php
```

## Consequences

### Positive

| Benefit | Description |
|---------|-------------|
| **High Cohesion** | Related code stays together |
| **AI-Native** | Agents (Cursor/Windsurf) find all context in one directory |
| **Zero-Side-Effect Refactoring** | Changes are isolated to the slice |
| **Onboarding** | New developers understand a feature by reading one folder |
| **Deletion-Friendly** | Removing features is safe and complete |

### Negative

| Risk | Mitigation |
|------|------------|
| **Code Duplication** | Accepted: *Duplication is far cheaper than the wrong abstraction* |
| **No Global Overview** | Use grep/search to find all features |
| **Multiple Entry Points Confusion** | Consistent naming convention (`*Action.php`, `*Command.php`) |

## Shared Architecture

> **TL;DR:** Shared is for glue, not for domain. See [ADR-0009](0009-shared-architecture.md) for complete rules.

### Two-Level Shared System

| Level | Location | Purpose |
|-------|----------|---------|
| **Global Shared** | `src/Shared/` | Infrastructure glue (Messenger, Sentry, base exceptions) |
| **Module Shared** | `src/{Module}/` | Module entities, enums, services |

### Rule of Three

Don't extract code to Shared until it's needed in **3+ places**:

| Duplication | Action |
|-------------|--------|
| 1-2 places | Keep in feature (duplication is OK) |
| 3+ places | Extract to Shared |

### What Goes Where

| Type | Global Shared | Module Shared | Feature |
|------|---------------|---------------|---------|
| Messenger Bus | ✅ | ❌ | ❌ |
| Sentry Wrapper | ✅ | ❌ | ❌ |
| User Entity | ❌ | ✅ | ❌ |
| UserRole Enum | ❌ | ✅ | ❌ |
| PasswordHasher | ❌ | ✅ | ❌ |
| Domain Logic | ❌ | ❌ | ✅ |

## Compliance

1. **Generate with `make slice`**: All new features MUST use the scaffold script.
2. **Reject nesting in PRs**: Any Pull Request introducing sub-folders (`Input/`, `Output/`, `Handler/`) inside a Feature folder must be rejected.
3. **Rule of One Folder**: Deleting a feature folder must not leave broken references.

## Directory Structure

```
src/
├── Kernel.php              # System core (Symfony MicroKernel)
├── Shared/                 # Global Shared (infrastructure only)
│   ├── Infrastructure/
│   │   ├── Bus/           # Messenger configuration
│   │   ├── Persistence/   # Doctrine extensions
│   │   └── Logging/       # Sentry, monitoring
│   └── Domain/
│       ├── ValueObject/    # Global value objects
│       └── Exception/      # Base exceptions
├── User/                   # Module
│   ├── Entity/             # User.php
│   ├── Enum/               # UserRole.php
│   ├── Service/            # PasswordHasher.php
│   ├── Events/             # UserRegisteredEvent.php
│   ├── Repositories/
│   └── Features/           # Vertical Slices
│       └── {FeatureName}/
│           ├── {FeatureName}Action.php
│           ├── {FeatureName}Handler.php
│           ├── {FeatureName}Dto.php
│           └── {FeatureName}HandlerTest.php
├── Task/                   # Module (same pattern)
├── Board/                  # Module (same pattern)
└── Health/                 # Technical feature (same pattern)
```

> **Note:** Module Shared folders (`Entity/`, `Enum/`, `Service/`, `Events/`) are optional. Create only when code is used across 3+ features within the module.

---
src/
├── Kernel.php              # System core (Symfony MicroKernel)
├── Shared/                 # Truly shared (infrastructure only)
│   ├── Exception/
│   └── Services/
├── User/                   # Module
│   ├── Entity/
│   ├── Enums/
│   ├── ValueObject/
│   ├── Event/
│   ├── Services/
│   ├── Clients/
│   ├── Repositories/
│   ├── Exception/
│   └── Features/           # Vertical Slices
│       └── {FeatureName}/
│           ├── {FeatureName}Action.php
│           ├── {FeatureName}Command.php
│           ├── {FeatureName}Handler.php
│           ├── {FeatureName}Dto.php
│           └── {FeatureName}HandlerTest.php
├── Task/                   # Module (same pattern)
├── Board/                  # Module (same pattern)
└── Health/                 # Technical feature (same pattern)
```

---

**Pragmatism over Dogma.** We build products, not folder structures.

*We optimize for Deletion, not for Reusability. A feature remains a feature regardless of how it's invoked — whether via HTTP, CLI, or Message Bus. That's the essence of Vertical Slices.*
