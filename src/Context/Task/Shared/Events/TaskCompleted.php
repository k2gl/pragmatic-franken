<?php

declare(strict_types=1);

namespace App\Context\Task\Shared\Events;

use DateTimeImmutable;

final readonly class TaskCompleted
{
    public function __construct(
        public string $taskId,
        public string $title,
        public DateTimeImmutable $completedAt,
    ) {}
}
