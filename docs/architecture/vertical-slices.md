# Vertical Slices Architecture — Quick Guide

## Principles

| Principle | Description |
|-----------|-------------|
| **Slices** | Code grouped by business features, not technical layers |
| **CQRS** | Command (write) and Query (read) separation |
| **Low Coupling** | Minimum dependencies between slices |
| **High Cohesion** | Everything for a feature in one folder |

## Project Structure

```
src/
├── Shared/                      # Infrastructure (exceptions, services)
│   ├── Exception/
│   └── Services/
│
├── User/                       # Module
│   ├── Entity/
│   ├── Enums/
│   ├── UseCase/
│   │   └── {FeatureName}/
│   │       ├── Input/
│   │       ├── Output/
│   │       ├── Handler/
│   │       ├── EntryPoint/
│   │       │   ├── Http/
│   │       │   └── Cli/
│   │       └── Client/
│
├── Board/                      # Module
│   └── ...
│
├── Task/                       # Module
│   └── ...
│
└── Health/                     # Technical feature
    ├── Services/
    └── UseCase/
```

## What Goes to Shared?

### Extract (Always)
- Infrastructure wrappers (Helpers)
- Base exceptions

### Do NOT Extract (Never)
- Business logic
- Similar code in different slices (WET > DRY)

## Naming Conventions

| Type | Example |
|------|---------|
| UseCase | `CreateUser/` |
| Command | `CreateUserCommand.php` |
| Query | `CreateUserQuery.php` |
| Response | `CreateUserResponse.php` |
| Handler | `CreateUserHandler.php` |
| Controller | `CreateUserController.php` |
| EntryPoint/Http | `CreateUserController.php` |

## Configuration

### services.yaml
```yaml
App\:
    resource: '../src/'
    exclude:
        - '../src/Shared/{Exception,Services}/'
        - '../src/*/{Entity,Enums}/'

App\**\*Controller:
    tags: ['controller.service_arguments']

App\**\*Handler:
    tags: ['messenger.message_handler']
```

### routes.yaml
```yaml
controllers:
    resource: ../src/
    type: attribute
```

## Key Tools

1. **Symfony Messenger** — Command/Query Bus
2. **Deptrac** — Module boundary control
3. **PHPStan** — Static analysis

## AI Agent Benefits

- **Context localization**: One folder = one AI request
- **Simple testing**: One input → one output
- **Safe changes**: Changing one slice doesn't break others

## References

- [ADR 1: Vertical Slices Architecture](0001-vertical-slices.md)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Deptrac](https://github.com/qossmic/deptrac)
