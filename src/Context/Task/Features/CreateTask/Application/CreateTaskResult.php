<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\Application;

final readonly class CreateTaskResult
{
    public function __construct(
        public string $id,
        public string $title,
        public bool $completed,
        public string $createdAt,
    ) {}
}
