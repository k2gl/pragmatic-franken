# ADR 9: Shared Architecture

## Status

**Accepted**

## Context

The `Shared/` directory is the most dangerous place in any architecture. Without strict rules, it becomes a "trash bin" within 6 months, containing everything from string helpers to business logic that was afraid to be placed in a module.

### The Problem

| Anti-Pattern | Result |
|--------------|---------|
| CommonUtils.php | God class with 500+ unrelated methods |
| Helper.php | Place for code nobody wants to own |
| Shared Services | Business logic scattered across modules |
| Cross-module dependencies | Tight coupling, impossible to extract microservices |

### The Solution: Two-Level Shared System

We establish two levels of Shared to prevent contamination:

1. **Global Shared** (`src/Shared/`) — Cross-module infrastructure glue
2. **Module Shared** (`src/{Module}/`) — Module-level shared code

## Decision

### 1. Global Shared Structure

`src/Shared/` contains only infrastructure and base abstractions:

```
src/Shared/
├── Infrastructure/
│   ├── Bus/                    # Symfony Messenger configuration
│   │   ├── MessengerBus.php
│   │   └── EventBus.php
│   ├── Persistence/            # Doctrine extensions, base repositories
│   │   └── TimestampableTrait.php
│   └── Logging/                # Sentry, monitoring wrappers
│       └── SentryFeatureTagger.php
└── Domain/
    ├── ValueObject/            # Truly global value objects
    │   └── Uuid.php
    └── Exception/             # Base exceptions for the application
        ├── DomainException.php
        └── InvalidArgumentException.php
```

### 2. Module Shared Structure

`src/{Module}/Shared/` contains module-level shared code:

```
src/User/
├── Entity/                    # Module-wide entities
│   └── User.php
├── Enum/                      # Module-wide enums
│   └── UserRole.php
├── Service/                   # Module-domain services
│   └── PasswordHasher.php
└── Events/                    # Module events for cross-module communication
    └── UserRegisteredEvent.php
```

### 3. Rule of Three

**Don't extract code to Shared until it's needed in 3+ places.**

| Duplication Count | Action |
|-------------------|--------|
| 1 place | Keep in feature |
| 2 places | Accept duplication (it's cheaper than wrong abstraction) |
| 3 places | Extract to Shared |

**Example:**

```php
// Feature 1: Login
// Feature 2: RegisterUser
// Both need email validation
// → Extract to Shared/Domain/Validator/EmailValidator.php
```

### 4. What Belongs in Global Shared

| Category | Examples | Why |
|----------|----------|-----|
| **Infrastructure** | Messenger buses, Sentry wrapper, Doctrine extensions | Cross-cutting concerns, not business logic |
| **Base Exceptions** | DomainException, NotFoundException | Standard error handling across modules |
| **Global Value Objects** | Uuid, PaginationDto | Objects used everywhere |

### 5. What Belongs in Module Shared

| Category | Examples | Why |
|----------|----------|-----|
| **Entities** | User, Task, Board | Used across multiple features in the module |
| **Enums** | UserRole, TaskStatus | Status values used in multiple features |
| **Domain Services** | PasswordHasher, TokenGenerator | Module-specific infrastructure |
| **Events** | UserRegisteredEvent | Cross-module communication |

### 6. What is PROHIBITED in Shared

| Anti-Pattern | Solution |
|--------------|----------|
| **Handlers** | Business logic belongs in Features. If two features need the same handler, extract to Module Shared/Service/ |
| **God Classes** (CommonUtils.php, Helper.php) | Use dedicated libraries or create specific classes |
| **Feature-Specific Code** | Keep in the feature folder |
| **Business Logic** | Never. Features own their business logic. |

### 7. Code Examples

#### ✅ ALLOWED: Infrastructure in Global Shared

```php
// src/Shared/Infrastructure/Bus/EventBus.php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EventBus
{
    public function __construct(
        private MessageBusInterface $eventBus
    ) {}

    public function dispatch(object $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
```

#### ✅ ALLOWED: Module Entity in Module Shared

```php
// src/User/Entity/User.php
declare(strict_types=1);

namespace App\User\Entity;

use App\User\Enum\UserRole;

final class User
{
    public function __construct(
        private string $email,
        private string $password,
        private UserRole $role = UserRole::USER,
    ) {}
}
```

#### ✅ ALLOWED: Module Event in Module Shared

```php
// src/User/Events/UserRegisteredEvent.php
declare(strict_types=1);

namespace App\User\Events;

final readonly class UserRegisteredEvent
{
    public function __construct(
        public int $userId,
        public string $email,
    ) {}
}
```

#### ❌ PROHIBITED: God Class in Shared

```php
// WRONG - Never do this
// src/Shared/Domain/CommonUtils.php

final class CommonUtils
{
    public static function formatDate(DateTime $date): string { ... }
    public static function validateEmail(string $email): bool { ... }
    public static function slugify(string $text): string { ... }
    public static function arrayGroupBy(array $array, string $key): array { ... }
    // 50 more random methods...
}
```

**Solution:** Use dedicated libraries or create specific classes.

```php
// src/Shared/Infrastructure/Utils/StringSlugger.php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Utils;

final class StringSlugger
{
    public function slugify(string $text): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/', '-', $text));
    }
}
```

#### ❌ PROHIBITED: Handler in Shared

```php
// WRONG - Business logic in Shared
// src/Shared/Domain/Handler/SendEmailHandler.php

final class SendEmailHandler
{
    public function handle(SendEmailCommand $command): void
    {
        // Business logic that should be in a Feature
    }
}
```

**Solution:** Keep handler in the feature folder.

```php
// src/User/Features/SendWelcomeEmail/SendWelcomeEmailHandler.php
declare(strict_types=1);

namespace App\User\Features\SendWelcomeEmail;

#[AsMessageHandler]
final class SendWelcomeEmailHandler
{
    public function handle(SendWelcomeEmailCommand $command): void
    {
        // Business logic stays with the feature
    }
}
```

## Consequences

### Positive

| Benefit | Description |
|---------|-------------|
| **Clean Boundaries** | Infrastructure separated from domain logic |
| **Safe Refactoring** | Changes to Shared have predictable impact |
| **Testability** | Infrastructure can be mocked/stubbed easily |
| **No God Classes** | Code has clear ownership |

### Negative

| Risk | Mitigation |
|------|------------|
| **Over-Extraction** | Rule of Three prevents premature abstraction |
| **Module Bloat** | Module Shared is optional — don't create if not needed |
| **Documentation Burden** | Clear rules in ADR prevent misuse |

## Compliance

### 1. Code Review Checklist

- [ ] No handlers in Shared
- [ ] No God Classes (CommonUtils.php, Helper.php)
- [ ] No feature-specific code in Shared
- [ ] Code extracted only after 3+ usages (Rule of Three)
- [ ] Infrastructure in Global Shared, Domain in Module Shared

### 2. Automated Checks

Use Deptrac or PHPStan to enforce:

```yaml
# deptrac.yaml
ruleset:
  - from: Features
    to: Shared\Infrastructure
      allow: true
  - from: Features
    to: Shared\Domain
      allow: false  # Features cannot depend on Shared Domain
```

### 3. Commands

```bash
# Generate feature with proper structure
make slice module=User feature=RegisterUser

# Run compliance checks
make check
```

## Migration Guide

### Phase 1: Audit Existing Shared

```bash
# Find potential God Classes
find src/Shared/ -name "*.php" -exec wc -l {} \; | sort -rn | head

# Find feature-specific code
grep -r "FeatureName" src/Shared/ --include="*.php"
```

### Phase 2: Create Two-Level Structure

```bash
# Create Global Shared subdirectories
mkdir -p src/Shared/{Infrastructure/{Bus,Persistence,Logging},Domain/{ValueObject,Exception}}

# Create Module Shared directories (if needed by module)
mkdir -p src/{User,Task,Board}/Shared/{Entity,Enum,Service,Events}
```

### Phase 3: Gradual Migration

1. When adding new feature, check if Shared needs organization
2. When finding code in Shared, classify it as Global/Module/Feature
3. Move feature-specific code back to feature folder
4. Consolidate similar infrastructure code

---

**Manifesto:** We prefer duplication over the wrong abstraction. Shared is for glue, not for domain.

*The cleanest code is the code you can safely delete. Every class in Shared should have a clear purpose and owner.*
