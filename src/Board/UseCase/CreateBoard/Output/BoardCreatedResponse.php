<?php

declare(strict_types=1);

namespace App\Board\UseCase\CreateBoard\Output;

use OpenApi\Attributes as OA;

#[OA\Schema(title: "BoardCreatedResponse")]
final readonly class BoardCreatedResponse
{
    public function __construct(
        #[OA\Property(description: "Database ID of created board")]
        public int $id,

        #[OA\Property(description: "Public UUID of created board")]
        public string $uuid,

        #[OA\Property(description: "Title of created board")]
        public string $title
    ) {}
}
