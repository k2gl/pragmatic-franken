---
id: ADR-0002
title: Messenger Transport
status: Accepted
date: 2026-02-04
supersedes: []
superseded_by: []
audience: both
summary: "Symfony Messenger as the primary bus. CQRS: Commands and Queries are synchronous; Events are asynchronous on the Doctrine transport (production-proven; swap the DSN for Redis/AMQP when throughput demands it)."
---

# ADR-0002: Messenger Transport

**TL;DR:** Writes dispatch a `*Command`, reads a `*Query` — both synchronous. Cross-feature side effects are asynchronous `*Event`s on the Doctrine transport (`doctrine://default?auto_setup=true` — no extra PHP extension, inspectable queue table, production-proven; swap the DSN for Redis/AMQP when throughput demands it). ADR-0003 defines when this rule may be skipped (single-writer CRUD, legacy migration, perf-critical hot paths).

## Context

Controllers calling services directly create tight coupling and hard-to-test code. We needed UI separated from business logic, loose coupling between modules, and one mechanism for both synchronous and asynchronous operations.

## Decision

**Symfony Messenger** as the main communication bus, CQRS-style:

- **Commands** — write operations (synchronous)
- **Queries** — read operations (synchronous)
- **Events** — cross-context communication (asynchronous by default)

See [ADR-0003](0003-pragmatic-symfony-architecture.md) for the Message Bus Rule and its escape hatches.

## Implementation

### 1. Command Bus — write operations

```php
// src/Context/Task/Features/CreateTask/Application/Message/CreateTaskCommand.php
final readonly class CreateTaskCommand
{
    public function __construct(
        public string $title,
    ) {}
}
```

```php
// src/Context/Task/Features/CreateTask/Application/CreateTaskHandler.php
#[AsMessageHandler]
final readonly class CreateTaskHandler
{
    public function __construct(
        private TaskRepository $tasks,
    ) {}

    public function __invoke(CreateTaskCommand $command): CreateTaskResult
    {
        // Business logic here
    }
}
```

### 2. Query Bus — read operations

`*Query` messages have the same shape; their handlers return `*Result` DTOs, never entities. Shipped example: `ListTasksQuery` → `ListTasksHandler` in `src/Context/Task/Features/ListTasks/`.

### 3. Event Bus — cross-context notifications

```php
// src/Context/Task/Features/CompleteTask/Domain/TaskCompleted.php
final readonly class TaskCompleted
{
    public function __construct(
        public string $taskId,
        public string $title,
        public DateTimeImmutable $completedAt,
    ) {}
}
```

Past-tense names, no `Event` suffix: `TaskCompleted`, not `TaskCompletedEvent` (ADR-0011). Async AI/LLM jobs are ordinary async messages — same pattern, no special machinery.

## Consequences

**Positive:** controllers stay free of business logic; handlers unit-test in isolation; implementations change without touching UI; async events enable eventual consistency; commands are an audit trail of intent; messages map naturally onto distributed systems if that day ever comes.

**Negative:** more classes per operation (message + handler + DTOs); Messenger learning curve; stack traces span multiple classes; small bus-middleware overhead.

## Guidelines

| Scenario | Bus | Example |
|----------|-----|---------|
| Create/Update/Delete state | Command Bus | `CreateTaskCommand` |
| Trigger side effects | Command Bus | `PublishLiveUpdateCommand` |
| Retrieve data only | Query Bus | `ListTasksQuery` |
| Notify other modules | Event Bus | `TaskCompleted` |

One handler per message. Commands may return a Result DTO for the HTTP layer — never an entity (ADR-0016). Validate input before it reaches the handler (`#[MapRequestPayload]` on the Request DTO).

## References

- [Symfony Messenger Documentation](https://symfony.com/doc/current/messenger.html)
- [CQRS by Martin Fowler](https://martinfowler.com/bliki/CQRS.html)
- [Gregory Young on Commands and Events](https://www.youtube.com/watch?v=JINCbgxvy3U)
