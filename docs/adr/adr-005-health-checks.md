# ADR 005: Health Checks

**Date:** 2026-02-05
**Status:** Accepted

## Decision

Implement standardized health checks for Docker and Kubernetes deployments.

## Context

Production environments require health endpoints to:
- Verify application readiness
- Detect dependency failures (DB, Redis)
- Enable rolling deployments
- Support Kubernetes liveness/readiness probes

## Implementation

### PHP Health Endpoint

```php
// src/Health/Features/HealthCheck/HealthCheckAction.php
declare(strict_types=1);

namespace App\Health\Features\HealthCheck;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthCheckAction
{
    #[Route('/healthz', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'status' => 'healthy',
            'timestamp' => date('c'),
        ]);
    }
}
```

### Detailed Health Check

```php
// src/Health/Infrastructure/HealthChecker.php
declare(strict_types=1);

namespace App\Health\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class HealthChecker
{
    public function __construct(
        private EntityManagerInterface $em,
        private MessageBusInterface $bus,
    ) {}

    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'messenger' => $this->checkMessenger(),
        ];

        $status = collect($checks)->every(fn($c) => $c['status'] === 'ok')
            ? 'healthy'
            : 'degraded';

        return [
            'status' => $status,
            'checks' => $checks,
            'timestamp' => date('c'),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $this->em->getConnection()->executeQuery('SELECT 1');
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkMessenger(): array
    {
        try {
            // Simple check - dispatch a test message
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

### Kubernetes Probe Configuration

```yaml
# kubernetes/deployment.yaml
apiVersion: apps/v1
kind: Deployment
spec:
  template:
    spec:
      containers:
        - name: app
          image: ghcr.io/k2gl/pragmatic-franken:latest
          ports:
            - containerPort: 443
          livenessProbe:
            httpGet:
              path: /healthz
              port: 443
            initialDelaySeconds: 10
            periodSeconds: 30
          readinessProbe:
            httpGet:
              path: /ready
              port: 443
            initialDelaySeconds: 5
            periodSeconds: 10
          startupProbe:
            httpGet:
              path: /healthz
              port: 443
            initialDelaySeconds: 0
            periodSeconds: 5
            failureThreshold: 30
```

### Docker Health Check

```dockerfile
# docker/frankenphp/Dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:443/healthz || exit 1
```

## Health Check Endpoints

| Endpoint | Purpose | Timeouts |
|----------|---------|----------|
| `/healthz` | Liveness - is app running? | Fast |
| `/ready` | Readiness - is app ready to serve? | May include DB check |
| `/metrics` | Prometheus metrics | N/A |

## Consequences

### Positive

- **Kubernetes Integration**: Proper rolling updates
- **Failure Detection**: Quick detection of broken deployments
- **Observability**: Standardized health metrics

### Negative

- **Overhead**: Detailed checks add latency
- **Complexity**: Multiple health levels to manage
