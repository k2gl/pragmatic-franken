<?php

declare(strict_types=1);

namespace App\Tests\Context\Task\Features\CompleteTask;

use App\Context\Task\Features\CompleteTask\Domain\TaskCompleted;
use App\Tests\Support\Factory\TaskFactory;
use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('e2e')]
final class CompleteTaskTest extends ApiTestCase
{
    use InteractsWithMessenger;

    public function test_completes_task_and_emits_domain_event(): void
    {
        $task = TaskFactory::createOne(['title' => 'Close the loop']);

        $this->sendJsonRequest('POST', sprintf('/tasks/%s/complete', $task->id()));

        fact($this->responseStatusCode())->is(200);
        fact($this->responseReader()->nested('data')->bool('completed'))->true();

        // Domain event routed async (ADR-0011) — queued, not handled inline.
        $this->transport('async')->queue()->assertContains(TaskCompleted::class, 1);
    }

    public function test_completing_twice_emits_once(): void
    {
        $task = TaskFactory::new()->completed()->create();

        $this->sendJsonRequest('POST', sprintf('/tasks/%s/complete', $task->id()));

        fact($this->responseStatusCode())->is(200);
        $this->transport('async')->queue()->assertContains(TaskCompleted::class, 0);
    }

    public function test_unknown_task_yields_problem_json_404(): void
    {
        $this->sendJsonRequest('POST', sprintf('/tasks/%s/complete', '0197fd03-0000-7000-8000-000000000000'));

        fact($this->responseStatusCode())->is(404);
        fact($this->responseReader()->string('title'))->is('Not found.');
    }
}
