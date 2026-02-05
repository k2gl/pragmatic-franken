<?php

declare(strict_types=1);

namespace App\Board\Features\GetBoard;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "BoardResponse")]
final readonly class BoardResponse
{
    public function __construct(
        #[OA\Property(description: "Board ID")]
        public int $id,
        #[OA\Property(description: "Board title")]
        public string $title,
        #[OA\Property(description: "Board UUID for public sharing")]
        public string $uuid,

        /** @var ColumnDTO[] */
        #[OA\Property(description: "List of columns with tasks")]
        public array $columns,
        #[OA\Property(description: "Board settings (JSON)")]
        public ?array $settings = null
    ) {
    }
}

#[OA\Schema(title: "ColumnDTO")]
final readonly class ColumnDTO
{
    public function __construct(
        #[OA\Property(description: "Column ID")]
        public int $id,
        #[OA\Property(description: "Column name")]
        public string $name,
        #[OA\Property(description: "Column position for ordering")]
        public float $position,

        /** @var TaskDTO[] */
        #[OA\Property(description: "Tasks in this column")]
        public array $tasks,
        #[OA\Property(description: "Number of tasks in column")]
        public int $taskCount = 0
    ) {
    }
}

#[OA\Schema(title: "TaskDTO")]
final readonly class TaskDTO
{
    public function __construct(
        #[OA\Property(description: "Task ID")]
        public int $id,
        #[OA\Property(description: "Task UUID")]
        public string $uuid,
        #[OA\Property(description: "Task title")]
        public string $title,
        #[OA\Property(description: "Position for drag-drop ordering")]
        public float $position,
        #[OA\Property(description: "Task status")]
        public string $status,
        #[OA\Property(description: "Task description", nullable: true)]
        public ?string $description = null,
        #[OA\Property(description: "Assignee user ID", nullable: true)]
        public ?int $assigneeId = null,
        #[OA\Property(description: "Due date", nullable: true)]
        public ?string $dueDate = null,
        #[OA\Property(description: "Task metadata (tags, colors, etc.)")]
        public array $metadata = [],
        #[OA\Property(description: "Creation timestamp")]
        public string $createdAt
    ) {
    }
}
