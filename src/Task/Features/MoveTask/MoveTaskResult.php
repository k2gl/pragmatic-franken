<?php

declare(strict_types=1);

namespace App\Task\Features\MoveTask;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "Result of moving a task")]
final readonly class MoveTaskResult
{
    public function __construct(
        #[OA\Property(description: "Task ID")]
        public int $taskId,

        #[OA\Property(description: "Task title")]
        public string $title,

        #[OA\Property(description: "Previous status")]
        public string $previousStatus,

        #[OA\Property(description: "New status")]
        public string $newStatus,

        #[OA\Property(description: "When task was updated")]
        public ?\DateTimeImmutable $updatedAt
    ) {}
}
