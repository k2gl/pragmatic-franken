<?php

declare(strict_types=1);

namespace App\Context\Health\Features\Healthz\Application;

use App\Context\Health\Features\Healthz\Infrastructure\DbPingInterface;
use App\Context\Health\Features\Healthz\Infrastructure\RedisPingInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckHealthHandler
{
    public function __construct(
        private DbPingInterface $dbPing,
        private RedisPingInterface $redisPing,
    ) {
    }

    public function __invoke(CheckHealthQuery $query): HealthStatus
    {
        return new HealthStatus(
            db: $this->dbPing->isAlive(),
            redis: $this->redisPing->isAlive(),
        );
    }
}
