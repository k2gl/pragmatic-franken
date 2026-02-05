<?php

declare(strict_types=1);

namespace App\Factory;

use App\Board\Entity\Board;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class BoardFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Board::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(3),
            'owner' => UserFactory::new(),
            'settings' => ['color' => '#3498db', 'icon' => 'kanban'],
            'isActive' => true,
        ];
    }

    public function withColumns(int $count = 3): self
    {
        return $this->afterCreating(function (Board $board) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                ColumnFactory::new([
                    'board' => $board,
                    'position' => (string) ($i * 1000),
                ])->create();
            }
        });
    }

    public function inactive(): self
    {
        return $this->with(['isActive' => false]);
    }

    public function withSettings(array $settings): self
    {
        return $this->with(['settings' => $settings]);
    }
}
