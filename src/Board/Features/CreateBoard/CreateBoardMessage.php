<?php

namespace App\Board\Features\CreateBoard;

use App\User\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Request to create a new Kanban board")]
final class CreateBoardMessage
{
    public ?User $user = null;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        #[OA\Property(description: "Board title", example: "My Project Kanban")]
        public string $title,

        /** @var ColumnInput[] */
        #[Assert\Valid]
        #[Assert\Count(min: 1, max: 20)]
        #[OA\Property(
            description: "Initial columns for the board",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/ColumnInput")
        )]
        public array $columns = []
    ) {}

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}

#[OA\Schema(description: "Column configuration for new board")]
final readonly class ColumnInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        #[OA\Property(description: "Column name", example: "Backlog")]
        public string $name,

        #[Assert\PositiveOrZero]
        #[OA\Property(description: "Column position order", example: 0)]
        public int $position = 0
    ) {}
}
