<?php

declare(strict_types=1);

namespace App\Tests\Context\Task\Features\CreateTask;

use App\Tests\Support\Factory\TaskFactory;
use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Constraints\NotBlank;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('e2e')]
final class CreateTaskTest extends ApiTestCase
{
    public function test_creates_task(): void
    {
        $this->sendJsonRequest('POST', '/tasks', json: ['title' => 'Ship Wave 2']);

        fact($this->responseStatusCode())->is(201);
        fact($this->responseReader()->string('title'))->is('Ship Wave 2');
        fact($this->responseReader()->bool('completed'))->false();
        fact(TaskFactory::repository()->count())->is(1);
    }

    public function test_blank_title_is_rejected_with_problem_json(): void
    {
        $this->sendJsonRequest('POST', '/tasks', json: ['title' => '']);

        $this->assertResponseContainsViolation('title', NotBlank::IS_BLANK_ERROR);
    }
}
