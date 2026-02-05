<?php

declare(strict_types=1);

namespace App\Task\UseCase\CreateTask\Output;

use App\Task\Entity\Task;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Task creation response")]
final readonly class TaskCreatedResponse
{
    public function __construct(
        #[OA\Property]
        public int $id,

        #[OA\Property]
        public string $uuid,

        #[OA\Property]
        public string $title,

        #[OA\Property]
        public int $columnId,

        #[OA\Property]
        public string $position,

        #[OA\Property]
        public string $status,

        #[OA\Property(nullable: true)]
        public ?string $description = null,

        #[OA\Property(nullable: true)]
        public ?array $metadata = null
    ) {}

    public static function fromEntity(Task $task): self
    {
        return new self(
            id: $task->getId(),
            uuid: $task->getUuid(),
            title: $task->getTitle(),
            columnId: $task->getColumn()->getId(),
            position: $task->getPosition(),
            status: $task->getStatus()->value,
            description: $task->getDescription(),
            metadata: $task->getMetadata()
        );
    }
}
