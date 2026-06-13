---
id: ADR-0011
title: Event Sourcing Lite
status: Accepted
date: 2026-04-29
supersedes: []
superseded_by: []
audience: both
summary: "Record state changes as immutable domain events dispatched via Messenger. No event store or projections — just first-class domain events that cross context boundaries asynchronously."
---

# ADR-0011: Event Sourcing Lite

**TL;DR:** Handlers record significant state changes as immutable domain events and dispatch them via `MessageBusInterface`. Downstream contexts subscribe to those events asynchronously. This gives the decoupling benefits of event-driven design without the operational cost of a full event store.

## Context

Full event sourcing (event store + projections + snapshots) is powerful but expensive to operate and reason about. Most features here do not need event replay — they need *decoupling*: notifying other bounded contexts without direct dependencies. Constraints: contexts must not call each other's internals (ADR-0001, ADR-0009); async side effects (emails, webhooks, analytics) must not block the primary request; removing a slice must not cascade.

## Decision

### 1. Domain events are immutable value objects

```php
// src/Context/Task/Shared/Events/TaskCompleted.php
final readonly class TaskCompleted
{
    public function __construct(
        public string $taskId,
        public string $title,
        public DateTimeImmutable $completedAt,
    ) {}
}
```

Rules:
- `final readonly` — no subclassing, no mutation.
- Past-tense naming: `TaskCompleted`, `UserRegistered`, `OrderShipped`.
- Events consumed only inside their feature live in `Domain/` of the originating slice.
- Events consumed by **other** bounded contexts live in `src/Context/{Name}/Shared/Events/` — an event with an external consumer is a context contract, not slice property; this keeps the originating slice deletable (ADR-0001). The shipped `TaskCompleted` lives there.

### 2. Handlers dispatch domain events via the bus

```php
// src/Context/Task/Features/CompleteTask/Application/CompleteTaskHandler.php
#[AsMessageHandler]
final readonly class CompleteTaskHandler
{
    public function __construct(
        private TaskRepository $tasks,
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CompleteTaskCommand $command): CompleteTaskResult
    {
        $task = $this->tasks->get($command->taskId);

        if (! $task->completed) {
            $task->complete();
            $this->tasks->save($task);

            $this->eventBus->dispatch(new TaskCompleted(
                taskId: (string) $task->id,
                title: $task->title->value,
                completedAt: $task->completedAt ?? new DateTimeImmutable,
            ));
        }
        // … build and return CompleteTaskResult
    }
}
```

### 3. Subscribers are independent slice handlers

A subscriber in another context is just a `#[AsMessageHandler]` handler for the domain event class — see `src/Context/Notification/Features/LiveUpdates/Application/OnTaskCompletedPublishLiveUpdate.php`, which reacts to `TaskCompleted` by publishing a Mercure live update. Route the event async in `config/packages/messenger.yaml` to decouple throughput:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            App\Context\Task\Shared\Events\TaskCompleted: async
```

Tests override the transport with the zenstruck/messenger-test sink (`config/packages/test/messenger.yaml`: `async: 'test://'`), so queued events are inspectable without a real consumer.

### 4. What "Lite" excludes

No event store (append-only log), no aggregates rebuilt from events, no projections/read models, no snapshots. Included: domain events as the decoupling mechanism and async side-effect handlers; an audit trail is optional (store events via a subscriber).

## Consequences

### Positive

- Bounded contexts stay decoupled — a subscriber can be added or removed without touching the originating slice.
- Async routing via Messenger means side effects (email, webhooks) never slow down the primary request.
- Events are self-documenting: reading `Domain/` shows exactly what state changes a feature produces.

### Negative

- Eventual consistency: subscribers run after the command handler, so there is a window where state is inconsistent from the subscriber's perspective.
- Events are not stored by default — add a `StoreDomainEventSubscriber` if auditability is required.
- Renaming an event class requires updating all subscribers; treat public events as a contract.

## References

- ADR-0001: Vertical Slices — slice location and domain event placement rules.
- ADR-0002: Messenger Transport — async routing configuration.
- ADR-0009: Shared Architecture — when to move events to `Shared/Events/`.
- `src/Context/Task/Shared/Events/TaskCompleted.php` (the event), `src/Context/Task/Features/CompleteTask/` (origin), `src/Context/Notification/Features/LiveUpdates/` (cross-context subscriber) — reference implementation.
