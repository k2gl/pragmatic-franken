<?php

declare(strict_types=1);

namespace App\Context\Task\Features\ListTasks\Application;

final readonly class ListTasksResult
{
    /**
     * @param list<TaskItem> $items
     */
    public function __construct(
        public array $items,
    ) {
    }
}
