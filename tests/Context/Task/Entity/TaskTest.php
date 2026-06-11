<?php

declare(strict_types=1);

namespace App\Tests\Context\Task\Entity;

use App\Context\Task\Entity\Task;
use App\Tests\Support\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('unit')]
final class TaskTest extends UnitTestCase
{
    public function test_new_task_is_not_completed(): void
    {
        $task = new Task('Write docs');

        fact($task->isCompleted())->false();
        fact($task->title())->is('Write docs');
        fact($task->completedAt())->null();
    }

    public function test_complete_is_idempotent(): void
    {
        $task = new Task('Write docs');

        $task->complete();
        $firstCompletedAt = $task->completedAt();

        $task->complete();

        fact($task->isCompleted())->true();
        fact($task->completedAt())->is($firstCompletedAt);
    }
}
