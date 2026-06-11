<?php

declare(strict_types=1);

namespace App\Context\Health\Features\Healthz\EntryPoint\Http;

use App\Context\Health\Features\Healthz\Application\CheckHealthQuery;
use App\Context\Health\Features\Healthz\Application\HealthStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Health probes per ADR-0005: /healthz is liveness (the process responds),
 * /ready is readiness (dependencies reachable). Same slice on purpose —
 * both are facets of one health feature.
 */
final class HealthzController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/healthz', name: 'app_healthz', methods: ['GET'])]
    public function healthz(): JsonResponse
    {
        return new JsonResponse(data: ['ok' => true]);
    }

    #[Route('/ready', name: 'app_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        /** @var HealthStatus $status */
        $status = $this->handle(new CheckHealthQuery());

        return new JsonResponse(
            data: [
                'ok' => $status->ok(),
                'db' => $status->db,
                'redis' => $status->redis,
            ],
            status: $status->ok() ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
