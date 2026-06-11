---
audience: both
tier: 2
last_reviewed: 2026-04-28
summary: "Concrete testing patterns: unit handlers, integration via Foundry+DAMA, e2e via WebTestCase, async via Messenger-Test. Architectural rules and coverage thresholds live in ADR-0008."
---

# Testing Guide

This guide is the *operational* complement to [ADR-0008](../adr/0008-testing-strategy.md). The ADR is the source of truth for the *what* (framework, layout, coverage thresholds, pyramid ratio); this guide is the *how* (concrete examples).

## Layout

Tests mirror `src/` one-to-one:

```
tests/
├── bootstrap.php
├── Support/                                   # framework helpers (not tests)
│   └── TestCase/{UnitTestCase,IntegrationTestCase,ApiTestCase}.php
└── {Context}/Features/{Feature}/{Feature}*Test.php
```

Test type is encoded by the base class plus a `#[Group]` attribute:

| Type | Base class | `#[Group]` | Make target |
|---|---|---|---|
| Unit | `UnitTestCase` (extends `PHPUnit\Framework\TestCase`) | `unit` | `make test-unit` |
| Integration | `IntegrationTestCase` (extends `KernelTestCase` + Foundry + DAMA) | `integration` | `make test-integration` |
| API / E2E | `ApiTestCase` (extends `WebTestCase`) | `e2e` | `make test-e2e` |

Coverage thresholds are defined in [ADR-0008](../adr/0008-testing-strategy.md#decision) and enforced in CI.

## Unit test (handler with mocked dependencies)

```php
namespace App\Tests\User\Features\Login;

use App\User\Features\Login\Application\LoginCommand;
use App\User\Features\Login\Application\LoginHandler;
use App\User\Features\Login\Application\LoginResult;
use App\User\Features\Login\Infrastructure\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class LoginHandlerTest extends TestCase
{
    public function test_returns_token_on_valid_credentials(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByEmail')->willReturn(/* ... */);

        $handler = new LoginHandler($repo);
        $result = $handler(new LoginCommand('user@example.com', 'password'));

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertNotEmpty($result->token);
    }
}
```

The reference unit test in this repo is [`tests/Context/Health/Features/Healthz/CheckHealthHandlerTest.php`](../../tests/Context/Health/Features/Healthz/CheckHealthHandlerTest.php).

## Integration test (real database, Foundry factories)

```php
namespace App\Tests\User\Features\Register;

use App\Tests\Support\TestCase\IntegrationTestCase;
use App\User\Features\Register\Application\RegisterCommand;
use App\User\Features\Register\Application\RegisterHandler;
use PHPUnit\Framework\Attributes\Group;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('integration')]
final class RegisterHandlerTest extends IntegrationTestCase
{
    use ResetDatabase;
    use Factories;

    public function test_persists_a_new_user(): void
    {
        $handler = self::getContainer()->get(RegisterHandler::class);
        $result = $handler(new RegisterCommand('user@example.com', 'password'));

        self::assertNotNull($result->id);
    }
}
```

`ResetDatabase` (Foundry) wraps every test in a transaction that rolls back on tearDown. `dama/doctrine-test-bundle` keeps the DB connection across the test for assertion convenience.

## E2E test (HTTP via WebTestCase)

```php
namespace App\Tests\Context\Health\Features\Healthz;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('e2e')]
final class HealthzControllerTest extends WebTestCase
{
    public function test_healthz_returns_json(): void
    {
        $client = self::createClient();
        $client->request('GET', '/healthz');

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertArrayHasKey('ok', $payload);
    }
}
```

The reference e2e test lives at [`tests/Context/Health/Features/Healthz/HealthzControllerTest.php`](../../tests/Context/Health/Features/Healthz/HealthzControllerTest.php).

## Async / Messenger

Use `zenstruck/messenger-test` to assert dispatched messages without booting a real worker:

```php
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[Group('integration')]
final class RegisterDispatchesEventTest extends IntegrationTestCase
{
    use InteractsWithMessenger;

    public function test_register_dispatches_user_registered_event(): void
    {
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new RegisterCommand(/* ... */));

        $this->transport('async')->queue()->assertContains(UserRegisteredEvent::class);
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

## Coverage thresholds

See [ADR-0008](../adr/0008-testing-strategy.md). Domain ≥ 90 %, Application ≥ 80 %, Infrastructure ≥ 60 %, UI ≥ 40 %. CI fails below these thresholds.
