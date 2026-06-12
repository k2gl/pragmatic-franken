---
audience: both
tier: 2
last_reviewed: 2026-04-28
summary: "Day-to-day development guide: setup, daily commands, slice scaffolding, common patterns. Architectural rules live in ADRs; this is the operational complement."
---

# Development Guide

## Prerequisites

- Docker & Docker Compose
- Make
- (Optional) PHP 8.5 locally for IDE / tooling integration

## Quick start

```bash
git clone https://github.com/k2gl/pragmatic-franken.git
cd pragmatic-franken
make install        # env-create + build + up + db-migrate
make smoke          # confirms bin/console boots and /ready responds
```

`make install` is idempotent: it creates `.env` from `.env.dist` (substituting your UID/GID), builds containers, brings them up, and runs Doctrine migrations.

## Daily commands

| Command | Effect |
|---|---|
| `make up` / `make down` | start / stop containers |
| `make shell` (alias `make e`) | shell inside the FrankenPHP container |
| `make logs` | follow container logs |
| `make test` | PHPUnit, fail-fast |
| `make test-unit` / `make test-integration` / `make test-e2e` | filtered by `#[Group]` |
| `make test-coverage` / `make coverage-html` | coverage reports |
| `make lint` / `make lint-check` | Pint (auto-fix / read-only) |
| `make analyze` | PHPStan level 10 |
| `make check` | lint + analyze (pre-commit) |
| `make ci` | lint-check + analyze + test (CI parity) |
| `make smoke` | end-to-end smoke check |
| `make db-migrate` / `make db-rollback` / `make db-fresh` | Doctrine migrations |
| `make slice context=Foo feature=Bar` | scaffold a new slice |
| `make adr title="My Decision"` | scaffold a new ADR |
| `make docs-check` | lint ADR front-matter and AGENTS.md budget |

## Project structure

See [ADR-0001](../adr/0001-vertical-slices.md) for the canonical layout. At a glance:

```
pragmatic-franken/
├── src/
│   ├── Kernel.php
│   ├── SharedKernel/           # cross-context infra (repo base, problem+json listeners)
│   └── Context/{Context}/
│       ├── Entity/  Repository/    # context-level model (ADR-0012, ADR-0013)
│       └── Features/{Feature}/
│           ├── Domain/             # value objects, domain events (optional)
│           ├── Application/        # Handler at root; Message/ (*Command|*Query), Dto/ (*Request|*Result)
│           ├── Infrastructure/     # adapters: HTTP clients, gateways (optional)
│           └── EntryPoint/Http/    # *Controller.php with #[Route]
├── tests/Context/{Context}/Features/{Feature}/  # mirrors src/, type via base class + #[Group]
├── config/  bin/  public/  assets/
├── dev/                        # codegen helpers (create-slice, new-adr, check-docs, worktree)
├── ops/                        # deploy
├── docs/{adr,guides,recipes}/
└── docker/
```

`Domain/` and `Infrastructure/` are optional inside a slice — create them only when the feature actually needs them.

## Creating a new slice

```bash
make slice context=Newsletter feature=Subscribe
```

This generates `src/Context/Newsletter/Features/Subscribe/` with `Application/SubscribeHandler.php`, `Application/Message/SubscribeCommand.php`, `Application/Dto/SubscribeResult.php`, `EntryPoint/Http/SubscribeController.php`, plus a matching `tests/Context/Newsletter/Features/Subscribe/SubscribeHandlerTest.php`. Open the files and replace the placeholders.

The reference slice for JSON endpoints is [`src/Context/Health/Features/Healthz/`](../../src/Context/Health/Features/Healthz/). The reference for Twig + AssetMapper is [`src/Context/Home/Features/Index/`](../../src/Context/Home/Features/Index/) (non-normative — drop it for API-only projects).

## Common patterns

### Validating input with attributes

Place validation on the `*Request` DTO (`Application/Dto/`) so it's deserialized and validated by `#[MapRequestPayload]`; the command stays a pure data carrier:

```php
namespace App\Context\Task\Features\CreateTask\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTaskRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
    ) {}
}
```

### Dispatching a command from a controller

Controllers use `HandleTrait::handle()`, build the command from the validated request and wrap the result in the `data` envelope (ADR-0016):

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateTaskController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/tasks', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateTaskRequest $request): JsonResponse
    {
        $result = $this->handle(new CreateTaskCommand($request->title));

        return new JsonResponse(['data' => $result], Response::HTTP_CREATED);
    }
}
```

The shipped reference is [`src/Context/Task/Features/CreateTask/`](../../src/Context/Task/Features/CreateTask/).

### Enums for status

```php
enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
}
```

### Returning DTOs, never entities

Query handlers return `*Result` DTOs. Don't expose Doctrine entities through HTTP — implicit lazy loading during JSON serialization causes hidden N+1.

## Code style & static analysis

- **Laravel Pint** (PSR-12 preset, `pint.json`). `make lint` auto-fixes; `make lint-check` is read-only.
- **PHPStan level 10** (`phpstan.neon`). `make analyze`.
- **Conventional Commits** for commit message headers.

`make check` runs lint + analyze before every commit; `make ci` adds tests. The CI `quality` job additionally runs the coverage floor, `docs-check`, `agent-smoke`, the OpenAPI dump and `composer audit`.

## Debugging

- **Logs:** `make logs`.
- **Xdebug:** port 9003, `make xdebug-on` / `make xdebug-off`. Host: `host.docker.internal` (or `docker.for.mac.localhost` on macOS).
- **Messenger:** `php bin/console messenger:failed` to inspect the dead-letter queue, `php bin/console messenger:retry` to retry.
- **Worker mode quirks:** see [`worker-mode.md`](worker-mode.md).
