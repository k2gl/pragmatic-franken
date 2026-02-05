<?php

declare(strict_types=1);

namespace App\Task\Features\MoveTask;

use App\Task\Enums\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Request to move task between columns")]
final readonly class MoveTaskMessage
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: "Task identifier", example: 1)]
        public int $taskId,

        #[Assert\NotNull]
        #[OA\Property(
            description: "New task status",
            example: "todo",
            type: "string",
            enum: ["backlog", "todo", "in_progress", "done"]
        )]
        public TaskStatus $newStatus
    ) {}

    public function withTaskId(int $taskId): self
    {
        return new self($taskId, $this->newStatus);
    }
}
