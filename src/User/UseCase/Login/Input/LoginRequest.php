<?php

declare(strict_types=1);

namespace App\User\UseCase\Login\Input;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "Login request for JWT token")]
final readonly class LoginRequest
{
    public function __construct(
        #[OA\Property(example: "user@example.com", description: "User email")]
        public string $username,

        #[OA\Property(example: "password123", description: "User password")]
        public string $password
    ) {}
}
