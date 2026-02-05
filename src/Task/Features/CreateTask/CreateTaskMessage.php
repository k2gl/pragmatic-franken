<?php

declare(strict_types=1);

namespace App\Task\Features\CreateTask;

use App\User\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Request to create a new task")]
final class CreateTaskMessage
{
    public ?User $user = null;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: "Column ID where task will be created", example: 1)]
        public int $columnId,

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        #[OA\Property(example: "Fix login bug")]
        public string $title,

        #[Assert\Length(max: 5000)]
        #[OA\Property(description: "Task description", nullable: true)]
        public ?string $description = null,

        /** @var array<string> */
        #[Assert\All(new Assert\Type(type: 'string'))]
        #[OA\Property(
            type: "array",
            items: new OA\Items(type: "string"),
            description: "Tags for the task"
        )]
        public array $tags = []
    ) {}

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
