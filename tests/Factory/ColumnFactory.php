<?php

declare(strict_types=1);

namespace App\Factory;

use App\Board\Entity\Column;
use App\Board\Entity\Board;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class ColumnFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Column::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->word(),
            'position' => (string) self::faker()->numberBetween(0, 10000),
            'taskCount' => 0,
            'settings' => ['color' => '#3498db'],
        ];
    }

    public function forBoard(Board $board): self
    {
        return $this->with(['board' => $board]);
    }

    public function withPosition(string $position): self
    {
        return $this->with(['position' => $position]);
    }
}
