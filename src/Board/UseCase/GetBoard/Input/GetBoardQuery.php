<?php

declare(strict_types=1);

namespace App\Board\UseCase\GetBoard\Input;

use App\User\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Query to fetch board with all columns and tasks")]
final class GetBoardQuery
{
    public ?User $user = null;

    public function __construct(
        #[Assert\Positive]
        #[OA\Property(description: "Board ID", example: 1)]
        public int $boardId
    ) {}

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
