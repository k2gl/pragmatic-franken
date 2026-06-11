<?php

declare(strict_types=1);

namespace App\Tests\Context\Task\Features\ListTasks;

use App\Tests\Support\Factory\TaskFactory;
use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('e2e')]
final class ListTasksTest extends ApiTestCase
{
    public function test_lists_tasks_newest_first(): void
    {
        TaskFactory::createMany(2);
        TaskFactory::new()->completed()->create(['title' => 'Done already']);

        $this->sendJsonRequest('GET', '/tasks');

        fact($this->responseStatusCode())->is(200);

        $items = $this->responseReader()->list('items');
        fact(\count($items))->is(3);
    }
}
