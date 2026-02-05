# ADR 6: Memory Management

**Date:** 2026-02-05
**Status:** Accepted

## Decision

Configure PHP and FrankenPHP for optimal memory usage in production, especially for AI workloads.

## Context

AI applications often require:
- Large context windows (memory-intensive)
- Long-running requests
- Efficient garbage collection
- Worker mode stability

## PHP Memory Configuration

### php.ini Settings

```ini
; Memory limit for AI workloads
memory_limit = 512M

; Increase for large inference requests
max_execution_time = 300

; Optimize garbage collection
zend.enable_gc = 1
gc_max_lifetime = 600

; OPcache for production
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 32
opcache.max_accelerated_files = 20000
```

### FrankenPHP Worker Mode

```caddy
# Caddyfile
{
    frankenphp {
        worker {
            file ./public/index.php
            num 4
            env PHP_MEMORY_LIMIT=512M
        }
    }
}

localhost {
    root * public
    php_server
}
```

### Environment Variables

```bash
# .env.prod
PHP_MEMORY_LIMIT=512M
PHP_MAX_EXECUTION_TIME=300
PHP_PM=dynamic
PHP_PM_MAX_CHILDREN=16
PHP_START_SERVERS=4
```

## Memory Optimization Strategies

### 1. Streaming Responses for AI

```php
// Stream AI responses to reduce peak memory
final readonly class StreamAiResponseAction
{
    public function __invoke(
        GenerateContentMessage $message,
        MessageBusInterface $bus,
    ): Response {
        return new StreamedResponse(function () use ($message, $bus) {
            $result = $bus->dispatch($message);
            foreach ($result->chunks() as $chunk) {
                echo $chunk;
                flush();
            }
        });
    }
}
```

### 2. Disable Xdebug in Production

```dockerfile
# docker/frankenphp/Dockerfile
RUN if [ "$APP_ENV" = "prod" ]; then \
    rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi
```

### 3. OPcache Configuration

```ini
; Validate timestamp hourly (not on every request)
opcache.revalidate_freq = 3600

; Enable file caching for generated files
opcache.file_cache = /tmp/opcache

; Disable save handlers that use memory
opcache.save_comments = 0
```

### 4. Garbage Collection Tuning

```php
// bootstrap.php
if (PHP_ENV === 'prod') {
    // Force GC run after every request with large memory usage
    if (memory_get_usage(true) > 128 * 1024 * 1024) {
        gc_collect_cycles();
    }
}
```

## Container Memory Limits

### Docker Compose

```yaml
services:
  frankenphp:
    build:
      context: .
      dockerfile: docker/frankenphp/Dockerfile
    mem_limit: 1g
    cpus: '2.0'
    environment:
      - PHP_MEMORY_LIMIT=512M
```

### Kubernetes Resource Limits

```yaml
# kubernetes/deployment.yaml
resources:
  requests:
    memory: "512Mi"
    cpu: "500m"
  limits:
    memory: "1Gi"
    cpu: "2000m"
```

## Memory Monitoring

### Prometheus Metrics

```php
// src/Shared/Infrastructure/Metrics/MemoryMetrics.php
declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

final readonly class MemoryMetrics
{
    public function getMetrics(): array
    {
        return [
            'php_memory_usage_bytes' => memory_get_usage(true),
            'php_memory_peak_bytes' => memory_get_peak_usage(true),
            'php_memory_limit_bytes' => $this->getMemoryLimit(),
        ];
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        return match (true) {
            str_ends_with($limit, 'G') => (int) ($limit) * 1024 * 1024 * 1024,
            str_ends_with($limit, 'M') => (int) ($limit) * 1024 * 1024,
            str_ends_with($limit, 'K') => (int) ($limit) * 1024,
            default => (int) $limit,
        };
    }
}
```

## Consequences

### Positive

- **Stable Workers**: No OOM crashes in long-running processes
- **Predictable Performance**: Consistent memory usage
- **Cost Efficiency**: Right-sized containers

### Negative

- **Configuration Complexity**: Multiple layers to configure
- **Debugging Difficulty**: Harder to diagnose memory issues
- **Trade-offs**: More memory = higher costs

## References

- [FrankenPHP Worker Mode](https://frankenphp.dev/docs/worker-mode/)
- [PHP Memory Management](https://www.php.net/manual/en/book.memory.php)
- [Kubernetes Resource Management](https://kubernetes.io/docs/concepts/configuration/manage-resources-containers/)
