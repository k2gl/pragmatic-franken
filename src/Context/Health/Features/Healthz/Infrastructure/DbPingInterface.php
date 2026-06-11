<?php

declare(strict_types=1);

namespace App\Context\Health\Features\Healthz\Infrastructure;

interface DbPingInterface
{
    public function isAlive(): bool;
}
