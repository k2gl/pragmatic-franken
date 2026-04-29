<?php

declare(strict_types=1);

namespace App\Notification\Features\LiveUpdates\Application;

final readonly class LiveUpdateResult
{
    public function __construct(
        public string $messageId,
    ) {
    }
}
