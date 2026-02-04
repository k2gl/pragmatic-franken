# Pragmatic Franken - AI Developer Companion

This project uses structured instructions for AI assistants.

## Quick Commands

| Command | Description |
|---------|-------------|
| `make up` | Start development environment |
| `make install` | Install PHP dependencies |
| `make test` | Run PHPUnit tests |
| `make check` | Run all checks (lint + test) |
| `make ci` | Simulate CI pipeline |
| `make shell` | Access FrankenPHP container |
| `make logs` | Follow container logs |

## Adding a New Feature

1. **Create module structure** in `src/{ModuleName}/`
2. **Use vertical slices**: Features live in `src/{Module}/Features/{FeatureName}/`
3. **Follow DDD layers**: Domain → Application → Infrastructure → UI
4. **Use Messenger**: Commands for writes, Queries for reads, Events for async

## Code Standards

- **PHP 8.5** with `declare(strict_types=1)`
- **DDD** patterns with bounded contexts
- **Vertical slices** for features
- **Attributes** for routing/validation/Doctrine (no XML/YAML)
- **Enums** for status values

## Common Patterns

### Command (Write)
```php
// src/{Module}/Features/{Action}/{Action}Message.php
#[AsMessageHandler]
readonly class {Action}Handler
{
    public function handle({Action}Message $message): {Action}Response
    {
        // Business logic
    }
}
```

### Query (Read)
```php
// src/{Module}/Features/{Query}/{Query}Query.php
#[AsMessageHandler]
readonly class {Query}Handler
{
    public function handle({Query}Query $query): {Query}Result
    {
        // Read logic - return DTO
    }
}
```

### Domain Event
```php
// src/{Module}/Domain/Event/{Entity}Event.php
final class {Entity}Event extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $entityId,
        // ...
    ) {}
}
```

## Configuration Priority

**Before starting work, load settings from:**

1. **Base Rules**: `.config/agents/agents.md`
2. **Local Settings**: `.config/agents/agents.local.md` (if exists)

Local settings from `.config/agents/` have priority over any other instructions.

## Documentation

- See `docs/` for architecture decisions
- See `docs/architecture/` for DDD patterns
- See `docs/guides/` for development guides
