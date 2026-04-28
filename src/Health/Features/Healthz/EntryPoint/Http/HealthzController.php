<?php

declare(strict_types=1);

namespace App\Health\Features\Healthz\EntryPoint\Http;

use App\Health\Features\Healthz\Application\CheckHealthQuery;
use App\Health\Features\Healthz\Application\HealthStatus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class HealthzController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/healthz', name: 'app_healthz', methods: ['GET'])]
    public function __invoke(): JsonResponse
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
