<?php

declare(strict_types=1);

namespace App\Health\Features\HealthCheck;

use App\Health\Infrastructure\HealthChecker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health', name: 'health', methods: ['GET'])]
readonly class HealthCheckAction
{
    public function __construct(private HealthChecker $healthChecker) {}

    public function __invoke(): JsonResponse
    {
        $status = $this->healthChecker->check();

        return new JsonResponse([
            'status' => $status['status'],
            'checks' => $status['checks'],
            'timestamp' => (new \DateTime())->format(\DateTime::ISO8601),
        ], $status['status'] === 'healthy' ? 200 : 503);
    }
}
