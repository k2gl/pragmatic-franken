# ADR 002: FrankenPHP Runtime

**Date:** 2026-02-04
**Status:** Accepted

## Decision

Use FrankenPHP as the primary application server for PHP applications.

## Context

We needed a modern application server that provides:
- Superior performance over traditional PHP-FPM
- Native support for Symfony Messenger workers
- HTTP/3 and automatic HTTPS
- Worker Mode for long-running processes
- 103 Early Hints support

## Consequences

### Positive

- **Performance**: Go-based Caddy integration provides 2-3x throughput over PHP-FPM
- **Worker Mode**: No separate consumer processes needed
- **Early Hints**: 103 responses improve perceived loading time by 30-50%
- **Mercure Native**: Built-in real-time updates without separate server
- **Simplified Infrastructure**: Single binary instead of PHP-FPM + Caddy + Supervisor

### Negative

- Learning curve for developers accustomed to PHP-FPM
- Debugging requires understanding FrankenPHP worker lifecycle

## Technical Details

### Performance Comparison

| Metric | PHP-FPM | FrankenPHP |
|--------|---------|------------|
| Requests/sec | ~500 | ~1500 |
| Memory usage | ~256MB | ~128MB |
| Cold start | 500ms | 50ms |
| Worker Mode | Separate | Native |

### 103 Early Hints

```php
// FrankenPHP automatically sends 103 when:
// 1. Route is preloading entities
// 2. Preloading is configured in Caddyfile
```

## Stateless Design Required

FrankenPHP worker mode requires stateless application design.

### Requirements

1. **No static state**: Do not use static properties to store request data
2. **No session affinity**: Workers handle multiple requests, avoid in-memory caches
3. **External caching**: Use Redis/Memcached for shared state
4. **Clean shutdown**: Handle signals properly for graceful worker shutdown

### Anti-Patterns to Avoid

```php
// ❌ WRONG - static property across requests
static $cache = [];

public function getData(): array
{
    return $this->cache[$this->id] ?? [];
}

// ✅ CORRECT - stateless
public function getData(EntityManagerInterface $em, int $id): ?Entity
{
    return $em->find(Entity::class, $id);
}
```

### Worker Configuration

```caddy
# Caddyfile
{
    frankenphp {
        worker {
            file ./public/index.php
            num 4
        }
    }
}

localhost {
    root * public
    php_server
}
```

## Alternatives Considered

- **RoadRunner**: Good performance but requires separate binary
- **Swoole**: Excellent performance but limited Symfony integration
- **PHP-FPM**: Standard but lacks worker capabilities

## References

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [Symfony Runtime Component](https://symfony.com/doc/current/runtime.html)
