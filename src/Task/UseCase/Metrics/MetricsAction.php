<?php

declare(strict_types=1);

namespace App\Task\UseCase\Metrics;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'System')]
#[Route('/metrics', methods: ['GET'])]
final class MetricsAction extends AbstractController
{
    public function __invoke(): Response
    {
        $metrics = [];

        $metrics[] = '# HELP php_memory_usage_bytes Memory usage in bytes';
        $metrics[] = '# TYPE php_memory_usage_bytes gauge';
        $metrics[] = sprintf('php_memory_usage_bytes %d', memory_get_usage(true));

        $metrics[] = '# HELP php_memory_peak_bytes Peak memory usage in bytes';
        $metrics[] = '# TYPE php_memory_peak_bytes gauge';
        $metrics[] = sprintf('php_memory_peak_bytes %d', memory_get_peak_usage(true));

        $metrics[] = '# HELP php_requests_total Total number of requests';
        $metrics[] = '# TYPE php_requests_total counter';
        $metrics[] = sprintf('php_requests_total %d', $_SERVER['REQUEST_TIME_FLOAT'] ?? 0);

        $metrics[] = '# HELP http_request_duration_seconds HTTP request duration';
        $metrics[] = '# TYPE http_request_duration_seconds histogram';
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.005"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.01"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.025"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.05"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.1"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.25"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="0.5"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="1"} %d', 0);
        $metrics[] = sprintf('http_request_duration_seconds_bucket{le="+Inf"} %d', 0);
        $metrics[] = '# TYPE http_request_duration_seconds histogram';

        return new Response(implode("\n", $metrics), 200, [
            'Content-Type' => 'text/plain; version=0.0.4',
        ]);
    }
}
