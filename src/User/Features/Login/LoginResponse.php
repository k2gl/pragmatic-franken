<?php

declare(strict_types=1);

namespace App\User\Features\Login;

use OpenApi\Attributes as OA;

#[OA\Schema(description: "JWT Token response")]
final readonly class LoginResponse
{
    public function __construct(
        #[OA\Property(description: "JWT Access Token")]
        public string $token,
        #[OA\Property(description: "Token type (Bearer)")]
        public string $tokenType,
        #[OA\Property(description: "Token expiration time in seconds")]
        public int $expiresIn,
        #[OA\Property(description: "User information")]
        public UserInfo $user
    ) {
    }

    public static function fromData(string $token, int $expiresIn, array $userData): self
    {
        return new self(
            $token,
            'Bearer',
            $expiresIn,
            new UserInfo(
                $userData['id'],
                $userData['email'],
                $userData['name']
            )
        );
    }
}

#[OA\Schema(description: "User information")]
final readonly class UserInfo
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name
    ) {
    }
}
