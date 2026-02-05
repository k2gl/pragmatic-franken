<?php

declare(strict_types=1);

namespace App\User\UseCase\RefreshToken\Input;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "Refresh token request")]
final readonly class RefreshTokenRequest
{
    public function __construct(
        #[OA\Property(description: "Refresh token string")]
        public string $refreshToken
    ) {}
}
