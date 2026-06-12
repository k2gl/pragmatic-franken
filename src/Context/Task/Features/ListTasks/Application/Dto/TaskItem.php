<?php

declare(strict_types=1);

namespace App\Context\Task\Features\ListTasks\Application\Dto;

final readonly class TaskItem
{
    public function __construct(
        public string $id,
        public string $title,
        public bool $completed,
        public string $createdAt,
    ) {}
}
