<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\Application\Message;

final readonly class CreateTaskCommand
{
    public function __construct(
        public string $title,
    ) {}
}
