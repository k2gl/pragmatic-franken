<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CompleteTask\Application;

final readonly class CompleteTaskResult
{
    public function __construct(
        public string $id,
        public string $title,
        public bool $completed,
        public string $completedAt,
    ) {
    }
}
