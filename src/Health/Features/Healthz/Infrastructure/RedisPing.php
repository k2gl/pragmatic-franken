<?php

declare(strict_types=1);

namespace App\Health\Features\Healthz\Infrastructure;

use Predis\ClientInterface;
use Throwable;

final readonly class RedisPing
{
    public function __construct(private ClientInterface $redis)
    {
    }

    public function isAlive(): bool
    {
        try {
            return (string) $this->redis->ping() !== '';
        } catch (Throwable) {
            return false;
        }
    }
}
