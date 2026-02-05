<?php

declare(strict_types=1);

namespace App\Health\UseCase\HealthCheck;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/healthz', name: 'health_check', methods: ['GET'])]
final class HealthCheckAction extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
        ]);
    }
}
