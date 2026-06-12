<?php

declare(strict_types=1);

namespace App\Context\Notification\Features\LiveUpdates\Application\Dto;

final readonly class LiveUpdateResult
{
    public function __construct(
        public string $messageId,
    ) {}
}
