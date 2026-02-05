# ADR 0003: Transactional Outbox Pattern

**Date:** 2026-02-04
**Status:** Accepted

## Decision

Implement the Outbox Pattern for guaranteed event delivery in high-availability scenarios.

## Context

We need to ensure that:
- Domain events are always published when entities change
- No events are lost during transaction failures
- At-least-once delivery is guaranteed
- Transactional integrity is maintained

## Consequences

### Positive

- **Guaranteed Delivery**: Events survive process crashes
- **Transactional Safety**: Events published atomically with entity changes
- **No 2PC**: No distributed transactions needed
- **Retry Support**: Failed dispatches can be retried

### Negative

- **Eventual Consistency**: Slight delay in event publishing
- **Storage Overhead**: Additional outbox table required
- **Complexity**: Requires background worker to process outbox

## Implementation

### 1. Entity with Domain Events

```php
class Order
{
    private array $events = [];
    
    public function complete(): void
    {
        $this->status = OrderStatus::COMPLETED;
        $this->events[] = new OrderCompletedEvent(
            $this->id,
            $this->customerId
        );
    }
    
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }
}
```

### 2. Outbox Table

```sql
CREATE TABLE outbox (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    message JSONB NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    dispatched_at TIMESTAMP WITH TIME ZONE,
    error TEXT
);

CREATE INDEX idx_outbox_created ON outbox(created_at) 
WHERE dispatched_at IS NULL;
```

### 3. Outbox Publisher (Symfony Messenger)

```php
#[AsMessageHandler]
final readonly class OutboxPublisher
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus
    ) {}
    
    public function __invoke(OutboxMessage $message): void
    {
        $connection = $this->em->getConnection();
        
        $connection->executeStatement(
            'SELECT id, message FROM outbox 
             WHERE dispatched_at IS NULL 
             ORDER BY created_at 
             LIMIT 100'
        );
        
        foreach ($rows as $row) {
            try {
                $event = json_decode($row['message'], true);
                $this->bus->dispatch($event);
                
                $connection->executeStatement(
                    'UPDATE outbox SET dispatched_at = NOW() WHERE id = ?',
                    [$row['id']]
                );
            } catch (\Exception $e) {
                $connection->executeStatement(
                    'UPDATE outbox SET error = ? WHERE id = ?',
                    [$e->getMessage(), $row['id']]
                );
            }
        }
    }
}
```

## Alternatives Considered

- **Transactional Outbox (this)**: Published in same transaction
- **Dual-Write**: Risky, events can be lost
- **Event Sourcing**: Overkill for most applications
- **CDC (Change Data Capture)**: Requires additional infrastructure

## Monitoring

Monitor for:
- Outbox processing lag (should be < 1 second)
- Failed dispatches (alerts on errors)
- Outbox table growth (should be pruned)

## References

- [Outbox Pattern by Microsoft](https://docs.microsoft.com/en-us/patterns/transactional-outbox)
- [ Symfony Messenger Transactions](https://symfony.com/doc/current/messenger.html#transactional-messages)
