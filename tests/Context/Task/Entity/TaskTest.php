<?php

declare(strict_types=1);

namespace App\Tests\Context\Task\Entity;

use App\Context\Task\Entity\Task;
use App\Context\Task\Shared\TaskTitle;
use App\Tests\Support\TestCase\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('unit')]
final class TaskTest extends UnitTestCase
{
    public function test_new_task_is_not_completed(): void
    {
        $task = Task::create('Write docs');

        fact($task->completed)->false();
        fact($task->title->value)->is('Write docs');
        fact($task->completedAt)->null();
    }

    public function test_complete_is_idempotent(): void
    {
        $task = Task::create('Write docs');

        $task->complete();
        $firstCompletedAt = $task->completedAt;

        $task->complete();

        fact($task->completed)->true();
        fact($task->completedAt)->is($firstCompletedAt);
    }

    public function test_blank_title_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Task::create('  ');
    }

    public function test_title_longer_than_max_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Task::create(str_repeat('a', TaskTitle::MAX_LENGTH + 1));
    }
}
