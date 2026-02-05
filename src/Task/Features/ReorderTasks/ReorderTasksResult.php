<?php

declare(strict_types=1);

namespace App\Task\Features\ReorderTasks;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "Result of reordering tasks")]
final readonly class ReorderTasksResult
{
    public function __construct(
        #[OA\Property(description: "Task ID")]
        public int $taskId,

        #[OA\Property(description: "New position")]
        public float $position,

        #[OA\Property(description: "Strategy used")]
        public string $strategy
    ) {}
}
