<?php

declare(strict_types=1);

namespace App\Notification\Features\LiveUpdates\Application;

final readonly class PublishLiveUpdateCommand
{
    public function __construct(
        public string $topic,
        public array $data,
        public bool $private = false,
    ) {
    }
}
