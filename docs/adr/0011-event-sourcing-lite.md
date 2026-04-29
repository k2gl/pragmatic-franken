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

Full event sourcing (event store + projections + snapshots) is powerful but expensive to operate and reason about. Most features in this boilerplate do not need event replay — they need *decoupling*: the ability to notify other bounded contexts about something that happened without creating direct dependencies.

### Forces

- Bounded contexts must not call each other's internals directly (ADR-0001, ADR-0009).
- Async side effects (emails, webhooks, analytics) should not block the primary request.
- The architecture must remain deletable — removing a slice must not cascade.

## Decision

### 1. Domain events are immutable value objects

```php
// src/Billing/Features/ProcessPayment/Domain/PaymentProcessed.php
final readonly class PaymentProcessed
{
    public function __construct(
        public string $paymentId,
        public int    $amountCents,
        public string $currency,
        public \DateTimeImmutable $processedAt,
    ) {}
}
```

Rules:
- `final readonly` — no subclassing, no mutation.
- Past-tense naming: `PaymentProcessed`, `UserRegistered`, `OrderShipped`.
- Intra-feature events live in `Domain/` of the originating slice.
- Events consumed by **other** bounded contexts move to `src/{Context}/Shared/Events/` — Rule of Three applies (ADR-0009).

### 2. Handlers dispatch domain events via the bus

```php
// src/Billing/Features/ProcessPayment/Application/ProcessPaymentHandler.php
#[AsMessageHandler]
final readonly class ProcessPaymentHandler
{
    public function __construct(private MessageBusInterface $eventBus) {}

    public function __invoke(ProcessPaymentCommand $command): ProcessPaymentResult
    {
        $paymentId = (string) Uuid::v7();
        $processedAt = new \DateTimeImmutable();

        $this->eventBus->dispatch(new PaymentProcessed(
            paymentId: $paymentId,
            amountCents: $command->amountCents,
            currency: $command->currency,
            processedAt: $processedAt,
        ));

        return new ProcessPaymentResult($paymentId, $command->amountCents, $command->currency, $processedAt);
    }
}
```

### 3. Subscribers are independent slice handlers

A subscriber in another context is just a `#[AsMessageHandler]` handler for the domain event class. Route it async in `config/packages/messenger.yaml` to decouple throughput:

```yaml
framework:
    messenger:
        routing:
            App\Billing\Features\ProcessPayment\Domain\PaymentProcessed: async
```

### 4. What "Lite" excludes

| Full Event Sourcing | Event Sourcing Lite |
|---|---|
| Event store (append-only log) | ❌ Not needed |
| Aggregate rebuilt from events | ❌ Not needed |
| Projections / read models | ❌ Not needed |
| Snapshots | ❌ Not needed |
| Domain events as decoupling mechanism | ✅ Included |
| Async side-effect handlers | ✅ Included |
| Audit trail via event log | ✅ Optional (store via a subscriber) |

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
- `src/Billing/Features/ProcessPayment/` — reference implementation.
