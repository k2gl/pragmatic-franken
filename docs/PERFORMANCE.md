# Performance Benchmarks

This document contains detailed performance benchmarks for Pragmatic Franken.

## FrankenPHP vs PHP-FPM

### Cold Boot Time

| Runtime | Cold Boot | Improvement |
|---------|-----------|-------------|
| PHP-FPM | ~150ms | baseline |
| FrankenPHP Worker | ~10ms | **15x faster** |

Cold boot is the time from request start to first byte sent. FrankenPHP's persistent process eliminates PHP compilation and opcache warmup.

### Requests per Second

| Runtime | Requests/sec | Improvement |
|---------|--------------|-------------|
| PHP-FPM | ~500 | baseline |
| FrankenPHP Worker | ~8,000 | **16x faster** |

*Note: Actual numbers depend on application complexity. Tests run on AWS t3.medium with 100 concurrent users.*

### Memory per Request

| Runtime | Memory/req | Improvement |
|---------|------------|-------------|
| PHP-FPM | ~2MB | baseline |
| FrankenPHP Worker | ~50KB | **40x less** |

FrankenPHP shares opcache and database connections across requests, dramatically reducing memory usage.

## Sources

- [FrankenPHP Benchmarks](https://frankenphp.dev/docs/benchmarks/)
- [TechEmpower Web Framework Benchmarks](https://www.techempower.com/benchmarks/)
- [Symfony Performance](https://symfony.com/doc/current/performance.html)
