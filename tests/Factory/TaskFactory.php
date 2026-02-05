<?php

declare(strict_types=1);

namespace App\Factory;

use App\Board\Entity\Column;
use App\Task\Entity\Task;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class TaskFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Task::class;
    }

    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(3),
            'description' => self::faker()->paragraph(),
            'column' => ColumnFactory::new()->create(),
            'owner' => UserFactory::new()->create(),
            'position' => '1000',
            'status' => 'backlog',
            'metadata' => [],
        ];
    }

    public function withTags(array $tags): self
    {
        return $this->with(['metadata' => ['tags' => $tags]]);
    }

    public function inColumn(Column $column): self
    {
        return $this->with(['column' => $column]);
    }

    public function withPosition(string $position): self
    {
        return $this->with(['position' => $position]);
    }
}
