# ADR 0004: Modular Monolith Architecture

**Date:** 2026-02-04
**Status:** Accepted

## Decision

Organize code as a Modular Monolith with Vertical Slice Architecture, keeping all modules in a single repository with clear boundaries.

## Context

We needed an architecture that:
- Starts simple but scales complexity
- Allows eventual microservices migration
- Enforces clear module boundaries
- Supports independent deployability within monorepo

## Consequences

### Positive

- **Clear Boundaries**: Each module is self-contained
- **Easy Refactoring**: Safe to change within module boundaries
- **Future-Proof**: Modules can be extracted to microservices
- **Team Scalability**: Multiple teams can work on different modules
- **Performance**: No network overhead between modules
- **Testing**: Simpler integration tests than distributed systems

### Negative

- **Discipline Required**: Boundaries must be enforced
- **Deployment**: Entire monolith deploys together
- **Scaling**: Can't scale individual modules independently

## Module Structure

```
src/
├── User/                    # Bounded Context
│   ├── Features/            # Vertical Slices
│   ├── Entity/              # Domain Model
│   ├── Repository/          # Persistence
│   └── Service/             # Application Services
├── Order/
│   ├── Features/
│   ├── Entity/
│   ├── Repository/
│   └── Service/
└── Shared/                  # Shared Kernel
    ├── Messaging/           # Cross-cutting
    └── Infrastructure/
```

## Module Boundaries Rules

1. **No Cross-Module Imports**: Modules cannot directly use other modules' entities
2. **Events for Communication**: Modules communicate via Domain Events
3. **Shared Kernel**: Only explicitly shared code goes to `Shared/`
4. **API Contracts**: Use DTOs for inter-module communication

### Example: User Module → Order Module

```php
// User module publishes event
class UserRegisteredEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email
    ) {}
}

// Order module subscribes via Messenger
#[AsMessageHandler]
final readonly class CreateWelcomeOrderHandler
{
    public function handle(UserRegisteredEvent $event): void
    {
        // Create initial order for user
    }
}
```

## When to Extract to Microservice

Consider extracting when:
- Different scaling requirements (CPU vs I/O heavy)
- Different technology needs
- Different release cadence
- Team ownership becomes critical

## References

- [Modular Monolith by Simon Brown](https://simonbrown.je/posts/modular-monolith/)
- [Vertical Slice Architecture](https://jimmybogard.com/vertical-slice-architecture/)
