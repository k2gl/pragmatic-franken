<?php

declare(strict_types=1);

namespace App\Health\Features\Healthz\Infrastructure;

interface RedisPingInterface
{
    public function isAlive(): bool;
}
