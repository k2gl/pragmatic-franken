# Architecture Documentation

## Project Structure

```
├── src/
│   ├── Kernel.php              # Symfony MicroKernel
│   ├── Task/                   # Task management module
│   │   ├── Features/           # Vertical slices
│   │   ├── Entity/             # Task entity
│   │   └── Domain/            # Domain logic
│   ├── Board/                  # Board management module
│   ├── User/                   # User & authentication
│   └── SharedKernel/           # Shared domain logic
├── public/
│   ├── js/                     # Vue.js frontend
│   │   ├── components/
│   │   ├── stores/             # Pinia stores
│   │   └── kanban-realtime.js   # Mercure SSE
│   └── index.php
├── config/
├── tests/
├── docker/
├── migrations/
└── docs/
```

## Vertical Slice Architecture

Each feature is self-contained in its own directory:

```
src/Task/Features/CreateTask/
├── CreateTaskAction.php      # Controller with #[Route]
├── CreateTaskMessage.php     # DTO + validation
├── CreateTaskHandler.php      # Business logic
└── CreateTaskResponse.php    # API response
```

## Design Patterns

### Repository Pattern
```php
readonly class TaskRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}
    
    public function find(int $id): ?Task { /* ... */ }
    public function getMaxPosition(int $columnId): float { /* ... */ }
}
```

### Domain Events
```php
#[AsMessageHandler]
readonly class TaskCompletedHandler
{
    public function handle(TaskCompletedEvent $event): void
    {
        // Notify users, update metrics, etc.
    }
}
```

## Database Design

### Fractional Indexing
```sql
CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    position DECIMAL(20, 10) NOT NULL DEFAULT 0,
    column_id INTEGER NOT NULL REFERENCES board_columns(id)
);

CREATE INDEX idx_tasks_position ON tasks(column_id, position);
```

### JSONB for Metadata
```php
$task->setMetadata([
    'tags' => ['bug', 'high-priority'],
    'color' => '#ff6b6b',
    'checklist' => [['item' => 'Review code', 'done' => true]]
]);
```

## Security

- JWT-based authentication via Symfony Security
- Role-based access control (RBAC)
- Input validation with Symfony Validator
- SQL injection prevention via Doctrine ORM
- XSS prevention via Symfony HTML sanitizer

## Performance

- OpCache enabled in production
- Database query optimization with indexes
- Redis caching for frequently accessed data
- Mercure for real-time updates (no polling)
