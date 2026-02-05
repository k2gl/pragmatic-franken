# Worker Mode Best Practices

FrankenPHP's Worker Mode allows your PHP application to boot once and handle thousands of requests without restarting. This is powerful but requires specific coding patterns.

## Why Worker Mode Matters

Traditional PHP-FPM boots, processes a request, and dies. Worker Mode keeps the process alive, caching opcache, database connections, and in-memory state. This delivers 3-5x performance gains—but only if your code respects the lifecycle.

## The Golden Rule

**Treat every request as if it might run forever.**

---

## Common Pitfalls

### ❌ Static Variables

```php
// BAD: State persists across requests
static $cache = [];

function getData($id) {
    if (!isset($cache[$id])) {
        $cache[$id] = db()->find($id);
    }
    return $cache[$id];
}
```

**Problem:** `$cache` grows infinitely. Memory leaks accumulate.

**Fix:** Use proper caching:

```php
// GOOD: External cache
function getData($id) {
    return $this->cache->getOrSet("data:$id", fn() => $this->repo->find($id));
}
```

---

### ❌ Static Counters

```php
// BAD: Counts persist forever
static $requestCount = 0;
$requestCount++;
```

**Problem:** Counter never resets. Useful for metrics, but ensure you have memory limits.

---

### ❌ Open Database Connections

```php
// BAD: Connection accumulates
$pdo = new PDO(...);
```

**Problem:** Each request opens a new connection. With Worker Mode, connections pile up.

**Fix:** Use Doctrine's connection pooling or let FrankenPHP manage connections.

---

### ❌ Singletons

```php
// BAD: Stateful singleton
class ServiceLocator {
    private static $instance;
    private $services = [];

    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

**Problem:** State leaks between requests.

**Fix:** Use dependency injection from the container.

---

### ❌ File Handles

```php
// BAD: File handle never closes
$handle = fopen('/tmp/cache.txt', 'r+');
```

**Problem:** Resource leak over time.

**Fix:** Use `try {} finally {}` or `register_shutdown_function()`.

---

## Worker-Safe Patterns

### ✅ Use Dependency Injection

```php
// Always inject dependencies
class UserService {
    public function __construct(
        private UserRepository $repo,
        private CacheInterface $cache
    ) {}
}
```

### ✅ Clean Up in Shutdown

```php
register_shutdown_function(function() {
    // Flush any pending logs
    $this->logger->flush();
    // Close external connections
    $this->connection->close();
});
```

### ✅ Use Framework Tools

Symfony's container and Doctrine already handle most edge cases:

- **EntityManager**: Automatically clears managed state
- **Container**: Returns fresh instances when configured
- **Messenger**: Handles worker lifecycle for async tasks

---

## Memory Management

### Monitor Usage

```bash
# Watch memory in container
docker stats frankenphp
```

### Restart Strategies

In `docker-compose.yml`:

```yaml
environment:
  PHP_MEMORY_LIMIT: 128M
  PHP_MAX_REQUESTS: 500  # Restart after 500 requests
  FRANKENPHP_MAX_JOBS: 8     # Max concurrent workers
```

### When to Reset

- **High traffic APIs**: Reset every 100-500 requests
- **Background workers**: Reset on job completion
- **CLI scripts**: Usually not needed (single execution)

---

## Async Workers with Messenger

FrankenPHP + Symfony Messenger = powerful async:

```php
// In your handler
#[AsMessageHandler]
class ProcessOrderHandler
{
    public function __invoke(ProcessOrder $command): void
    {
        // This runs in the same process
        // Perfect for stateful processing
    }
}
```

**Benefits:**
- No separate queue infrastructure (Redis + Messenger only)
- Keep database connections warm
- Share opcache between jobs

---

## Quick Checklist

| Check | Status |
|-------|--------|
| No `static` for caching | ☐ |
| No singletons | ☐ |
| DI over service locator | ☐ |
| External cache for hot data | ☐ |
| Cleanup on shutdown | ☐ |
| Memory limits configured | ☐ |

---

## References

- [ADR 0006: Memory Management](adr/0006-memory-management.md)
- [FrankenPHP Documentation](https://frankenphp.dev/docs/worker-mode/)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
