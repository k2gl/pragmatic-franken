<?php

declare(strict_types=1);

namespace App\Health\Features\Healthz\Infrastructure;

use Doctrine\DBAL\Connection;
use Throwable;

final readonly class DbPing
{
    public function __construct(private Connection $connection)
    {
    }

    public function isAlive(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
