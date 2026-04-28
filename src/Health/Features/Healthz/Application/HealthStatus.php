<?php

declare(strict_types=1);

namespace App\Health\Features\Healthz\Application;

final readonly class HealthStatus
{
    public function __construct(
        public bool $db,
        public bool $redis,
    ) {
    }

    public function ok(): bool
    {
        return $this->db && $this->redis;
    }
}
