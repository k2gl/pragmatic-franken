<?php

declare(strict_types=1);

namespace App\Tests\Support\Factory;

use App\Context\Task\Entity\Task;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Task>
 */
final class TaskFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Task::class;
    }

    protected function initialize(): static
    {
        // Task has a private constructor (ADR-0018) — build it via the named constructor.
        return $this->instantiateWith(Instantiator::namedConstructor('create'));
    }

    public function completed(): static
    {
        return $this->afterInstantiate(static function (Task $task): void {
            $task->complete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(3),
        ];
    }
}
