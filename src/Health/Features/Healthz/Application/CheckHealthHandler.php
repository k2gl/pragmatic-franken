<?php

declare(strict_types=1);

namespace App\Health\Features\Healthz\Application;

use App\Health\Features\Healthz\Infrastructure\DbPing;
use App\Health\Features\Healthz\Infrastructure\RedisPing;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckHealthHandler
{
    public function __construct(
        private DbPing $dbPing,
        private RedisPing $redisPing,
    ) {}

    public function __invoke(CheckHealthQuery $query): HealthStatus
    {
        return new HealthStatus(
            db: $this->dbPing->isAlive(),
            redis: $this->redisPing->isAlive(),
        );
    }
}
