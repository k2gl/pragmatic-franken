<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CompleteTask\Application;

final readonly class CompleteTaskCommand
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
