---
audience: both
tier: 2
last_reviewed: 2026-06-12
summary: "FrankenPHP worker-mode operational guide. The kernel stays in memory; static state must be reset or avoided. Pairs with ADR-0004 (decision) and ADR-0006 (memory)."
---

# Worker Mode Best Practices

FrankenPHP's Worker Mode allows your PHP application to boot once and handle thousands of requests without restarting. This is powerful but requires specific coding patterns.

## Why Worker Mode Matters

Traditional PHP-FPM boots, processes a request, and dies. Worker Mode keeps the process alive, reusing the booted kernel, opcache and database connections. That is the performance win — but only if your code respects the lifecycle.

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

### ❌ Open Database Connections

```php
// BAD: Connection accumulates
$pdo = new PDO(...);
```

**Problem:** Each request opens a new connection. With Worker Mode, connections pile up.

**Fix:** Inject Doctrine's connection from the container — the kernel reuses it across requests, and DoctrineBundle reconnects when it goes stale.

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

**Fix:** Wrap in `try {} finally {}` so the handle closes within the request.

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

### ✅ Clean up per request, not per process

`register_shutdown_function()` fires when the *worker* exits (after `FRANKENPHP_LOOP_MAX` requests) — not after each request. For per-request cleanup use a `kernel.terminate` listener, or implement `ResetInterface` / tag the service `kernel.reset` so symfony/runtime resets it between requests.

### ✅ Use Framework Tools

Symfony's container and Doctrine already handle most edge cases:

- **EntityManager**: reset between requests via `kernel.reset` (DoctrineBundle)
- **Your stateful services**: join that reset with `ResetInterface` / the `kernel.reset` tag
- **Messenger**: `messenger:consume` workers are long-lived too — the same rules apply

---

## Memory Management

### Monitor Usage

```bash
make docker-stats   # container resource usage
make stats          # FrankenPHP metrics
```

### Restart Strategies

Worker recycling is driven by symfony/runtime (see ADR-0006):

```yaml
environment:
  FRANKENPHP_LOOP_MAX: 500       # worker exits after N requests (default 500)
  FRANKENPHP_RESET_KERNEL: "1"   # optional: clone the kernel per request
```

`memory_limit` and OPcache settings live in `docker/php/prod-optimizations.ini`,
not in env vars.

### When to Recycle

- **High traffic APIs**: keep the default 500, lower it if memory grows between recycles
- **Leak-free, hot paths**: raise `FRANKENPHP_LOOP_MAX` for maximum throughput
- **CLI scripts**: not applicable (single execution)

---

## Async Workers with Messenger

Async messages run in `messenger:consume` processes — long-lived workers as well, so every rule above applies to handlers too.

**Benefits:**
- No separate broker — the async transport rides on Doctrine (`MESSENGER_TRANSPORT_DSN=doctrine://default`)
- Warm database connections and shared opcache between jobs

---

## Quick Checklist

| Check | Status |
|-------|--------|
| No `static` for caching | ☐ |
| No singletons | ☐ |
| DI over service locator | ☐ |
| External cache for hot data | ☐ |
| Per-request cleanup (`kernel.reset` / `kernel.terminate`) | ☐ |
| Memory limits configured | ☐ |

---

## References

- [ADR 0006: Memory Management](../adr/0006-memory-management.md)
- [FrankenPHP Documentation](https://frankenphp.dev/docs/worker-mode/)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
