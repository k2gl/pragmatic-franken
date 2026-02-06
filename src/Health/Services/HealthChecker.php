<?php

declare(strict_types=1);

namespace App\Health\Services;

use Doctrine\DBAL\Connection;

readonly class HealthChecker
{
    public function __construct(
        private ?Connection $connection = null,
        private ?\Redis $redis = null
    ) {
    }

    /**
     * @return array{status: string, checks: array{database: array{status: bool, message: string, latency_ms: float}, cache: array{status: bool, message: string, latency_ms: float}}}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $isHealthy = !in_array(false, array_column($checks, 'status'), true);

        return [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: bool, message: string, latency_ms: float}
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            if ($this->connection === null) {
                return ['status' => true, 'message' => 'Not configured', 'latency_ms' => 0];
            }

            $this->connection->executeQuery('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return ['status' => true, 'message' => 'Connected', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'latency_ms' => 0];
        }
    }

    /**
     * @return array{status: bool, message: string, latency_ms: float}
     */
    private function checkCache(): array
    {
        $start = microtime(true);

        try {
            if ($this->redis === null) {
                return ['status' => true, 'message' => 'Not configured', 'latency_ms' => 0];
            }

            $this->redis->ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return ['status' => true, 'message' => 'Connected', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'latency_ms' => 0];
        }
    }
}
