<?php

declare(strict_types=1);

namespace App\Context\Health\Features\Healthz\Infrastructure;

use Predis\ClientInterface;
use Throwable;

final readonly class RedisPing implements RedisPingInterface
{
    public function __construct(private ClientInterface $redis) {}

    public function isAlive(): bool
    {
        try {
            return (bool) $this->redis->ping();
        } catch (Throwable) {
            return false;
        }
    }
}
