---
audience: both
tier: 2
last_reviewed: 2026-06-12
summary: "Concrete testing patterns: unit handlers via UnitTestCase, integration via Foundry+DAMA, e2e via ApiTestCase, async via Messenger-Test, fluent fact() assertions. Architectural rules and coverage policy live in ADR-0008."
---

# Testing Guide

This guide is the *operational* complement to [ADR-0008](../adr/0008-testing-strategy.md). The ADR is the source of truth for the *what* (framework, layout, coverage policy, pyramid ratio); this guide is the *how*. Every example below is a trimmed copy of a real test in this repo — when in doubt, open the linked file.

## Layout

Tests mirror `src/` one-to-one:

```
tests/
├── bootstrap.php
├── Support/                                   # framework helpers (not tests)
│   ├── TestCase/{UnitTestCase,IntegrationTestCase,ApiTestCase}.php
│   └── Factory/                               # Foundry factories
└── Context/{Name}/Features/{Feature}/{Feature}*Test.php
```

Test type is encoded by the base class (`UnitTestCase` / `IntegrationTestCase` / `ApiTestCase`) plus the matching `#[Group]` attribute (`unit` / `integration` / `e2e`) — see the table in [ADR-0008](../adr/0008-testing-strategy.md#test-layout).

## Assertions

House style is the fluent `fact()` from `k2gl/phpunit-fluent-assertions` (the base classes use it themselves):

```php
use function K2gl\PHPUnitFluentAssertions\fact;

fact($status->ok())->true();
fact($repository->count())->is(2);
fact($result)->instanceOf(LiveUpdateResult::class);
```

For HTML pages, Symfony's DOM assertions (`assertSelectorTextContains()`, …) remain the right tool — see `tests/Context/Home/Features/Index/IndexControllerTest.php`.

## Unit test (handler with stubbed dependencies)

Trimmed from [`tests/Context/Health/Features/Healthz/CheckHealthHandlerTest.php`](../../tests/Context/Health/Features/Healthz/CheckHealthHandlerTest.php):

```php
namespace App\Tests\Context\Health\Features\Healthz;

use App\Context\Health\Features\Healthz\Application\CheckHealthHandler;
use App\Context\Health\Features\Healthz\Application\Message\CheckHealthQuery;
use App\Context\Health\Features\Healthz\Infrastructure\DbPingInterface;
use App\Context\Health\Features\Healthz\Infrastructure\RedisPingInterface;
use App\Tests\Support\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('unit')]
final class CheckHealthHandlerTest extends UnitTestCase
{
    public function test_returns_not_ok_when_db_is_down(): void
    {
        $db = $this->createStub(DbPingInterface::class);
        $db->method('isAlive')->willReturn(false);

        $redis = $this->createStub(RedisPingInterface::class);
        $redis->method('isAlive')->willReturn(true);

        $handler = new CheckHealthHandler($db, $redis);
        $status = $handler(new CheckHealthQuery);

        fact($status->ok())->false();
        fact($status->db)->false();
        fact($status->redis)->true();
    }
}
```

## Integration test (real database, Foundry factories)

`IntegrationTestCase` already carries Foundry's `Factories` + `ResetDatabase` — don't re-`use` them. Isolation: DAMA (registered in `phpunit.xml`) wraps each test in a transaction rolled back on teardown; `ResetDatabase` rebuilds the schema once up front. Trimmed from [`tests/Context/Task/Features/PurgeCompletedTasks/PurgeCompletedTasksHandlerTest.php`](../../tests/Context/Task/Features/PurgeCompletedTasks/PurgeCompletedTasksHandlerTest.php):

```php
namespace App\Tests\Context\Task\Features\PurgeCompletedTasks;

use App\Context\Task\Features\PurgeCompletedTasks\Application\PurgeCompletedTasksHandler;
use App\Tests\Support\Factory\TaskFactory;
use App\Tests\Support\TestCase\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('integration')]
final class PurgeCompletedTasksHandlerTest extends IntegrationTestCase
{
    public function test_purges_only_tasks_completed_before_retention(): void
    {
        $stale = TaskFactory::new()->completed()->create();
        $fresh = TaskFactory::new()->completed()->create();
        TaskFactory::createOne(); // open task — never purged

        $this->ageCompletedAt($stale, '-30 days'); // reflection helper — see the full test

        $handler = self::getContainer()->get(PurgeCompletedTasksHandler::class);
        \assert($handler instanceof PurgeCompletedTasksHandler);
        $handler();

        fact(TaskFactory::repository()->count())->is(2);
        fact(TaskFactory::repository()->find($fresh->id))->notNull();
    }
}
```

## E2E test (full HTTP via ApiTestCase)

`ApiTestCase` boots the full stack through `KernelBrowser` and ships helpers: `sendJsonRequest()` (pass `executor:` and override `authHeaders()` for your auth scheme — see the [JWT recipe](../recipes/jwt-auth.md)), `responseReader()` (type-safe body reads via `k2gl/array-reader`), `responseStatusCode()`, `assertResponseContainsViolation()` (422 problem+json), `postJson()` / `getJson()`. Trimmed from [`tests/Context/Task/Features/CreateTask/CreateTaskTest.php`](../../tests/Context/Task/Features/CreateTask/CreateTaskTest.php):

```php
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
        fact($this->responseReader()->nested('data')->string('title'))->is('Ship Wave 2');
        fact(TaskFactory::repository()->count())->is(1);
    }

    public function test_blank_title_is_rejected_with_problem_json(): void
    {
        $this->sendJsonRequest('POST', '/tasks', json: ['title' => '']);

        $this->assertResponseContainsViolation('title', NotBlank::IS_BLANK_ERROR);
    }
}
```

## Async / Messenger

`zenstruck/messenger-test` asserts queued messages without booting a worker. The shipped example is an e2e test: HTTP call → domain event queued on the `async` transport. Trimmed from [`tests/Context/Task/Features/CompleteTask/CompleteTaskTest.php`](../../tests/Context/Task/Features/CompleteTask/CompleteTaskTest.php):

```php
use App\Context\Task\Features\CompleteTask\Domain\TaskCompleted;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[Group('e2e')]
final class CompleteTaskTest extends ApiTestCase
{
    use InteractsWithMessenger;

    public function test_completes_task_and_emits_domain_event(): void
    {
        $task = TaskFactory::createOne(['title' => 'Close the loop']);

        $this->sendJsonRequest('POST', sprintf('/tasks/%s/complete', $task->id));

        fact($this->responseStatusCode())->is(200);
        // Domain event routed async (ADR-0011) — queued, not handled inline.
        $this->transport('async')->queue()->assertContains(TaskCompleted::class, 1);
    }
}
```

## Running tests

```bash
make test                   # all tests, fail-fast
make test-unit              # #[Group('unit')]
make test-integration       # #[Group('integration')]
make test-e2e               # #[Group('e2e')]
make test-coverage          # text coverage
make coverage-html          # HTML report (build/coverage/index.html)
```

## Coverage

CI enforces one global floor — 60 % of statements (`dev/check-coverage.php`). The per-layer targets in [ADR-0008](../adr/0008-testing-strategy.md#decision) are recommended fork policy, not a CI gate.
