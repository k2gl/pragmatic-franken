<?php

declare(strict_types=1);

namespace App\Task\Features\ReorderTasks;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Request to reorder tasks with fractional indexing")]
final readonly class ReorderTasksMessage
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: "Column identifier")]
        public int $columnId,

        /** @var array<int> */
        #[Assert\All([new Assert\Type('int')])]
        #[Assert\Count(min: 1)]
        #[OA\Property(
            description: "Task IDs in new order for bulk reorder",
            type: "array",
            items: new OA\Items(type: "integer")
        )]
        public array $orderedIds = [],
        #[Assert\Positive]
        #[OA\Property(description: "Task ID for single task move")]
        public ?int $taskId = null,
        #[Assert\Positive]
        #[OA\Property(description: "Previous task ID for fractional positioning")]
        public ?int $prevTaskId = null,
        #[Assert\Positive]
        #[OA\Property(description: "Next task ID for fractional positioning")]
        public ?int $nextTaskId = null,
        #[OA\Property(
            description: "Reorder strategy: bulk, between, at_top, at_bottom",
            type: "string",
            example: "between"
        )]
        public string $strategy = 'bulk'
    ) {
    }
}
