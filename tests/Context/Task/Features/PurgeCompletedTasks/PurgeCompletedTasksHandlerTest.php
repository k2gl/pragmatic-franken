<?php

declare(strict_types=1);

namespace App\Tests\Context\Task\Features\PurgeCompletedTasks;

use App\Context\Task\Entity\Task;
use App\Context\Task\Features\PurgeCompletedTasks\Application\PurgeCompletedTasksHandler;
use App\Context\Task\Repository\TaskRepository;
use App\Tests\Support\Factory\TaskFactory;
use App\Tests\Support\TestCase\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;
use DateTimeImmutable;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('integration')]
final class PurgeCompletedTasksHandlerTest extends IntegrationTestCase
{
    public function test_purges_only_tasks_completed_before_retention(): void
    {
        $stale = TaskFactory::new()->completed()->create();
        $fresh = TaskFactory::new()->completed()->create();
        TaskFactory::createOne(); // open task — never purged

        $this->ageCompletedAt($stale, '-30 days');

        $handler = self::getContainer()->get(PurgeCompletedTasksHandler::class);
        \assert($handler instanceof PurgeCompletedTasksHandler);
        $handler();

        $repository = TaskFactory::repository();
        fact($repository->count())->is(2);
        fact($repository->find($fresh->id()))->notNull();
    }

    private function ageCompletedAt(Task $task, string $modifier): void
    {
        $property = new ReflectionProperty(Task::class, 'completedAt');
        $property->setValue($task, new DateTimeImmutable($modifier));

        $tasks = self::getContainer()->get(TaskRepository::class);
        \assert($tasks instanceof TaskRepository);
        $tasks->save($task);
    }
}
