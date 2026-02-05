<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class UserFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'password' => 'hashed_' . self::faker()->password(),
            'name' => self::faker()->name(),
            'githubId' => self::faker()->optional()->numberBetween(10000, 99999),
            'githubUsername' => self::faker()->optional()->userName(),
            'avatarUrl' => self::faker()->optional()->imageUrl(),
            'roles' => ['ROLE_USER'],
        ];
    }

    public function admin(): self
    {
        return $this->with(['roles' => ['ROLE_ADMIN']]);
    }

    public function withGithub(string $username): self
    {
        return $this->with([
            'githubId' => self::faker()->numberBetween(10000, 99999),
            'githubUsername' => $username,
        ]);
    }
}
