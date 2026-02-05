# Development Guide

## Overview

This guide covers setting up your development environment and following project conventions.

## Prerequisites

- Docker & Docker Compose
- Make
- PHP 8.5+ (for local tooling only)

## Quick Start

```bash
# 1. Clone and enter directory
git clone git@github.com:your-org/pragmatic-franken.git
cd pragmatic-franken

# 2. Create environment file
make env-create

# 3. Build and start containers
make up

# 4. Install dependencies
make install

# 5. Run database migrations
make db-migrate

# 6. (Optional) Load fixtures
make db-seed

# 7. Verify installation
make test
```

## Daily Development Commands

### Shell Access

```bash
make shell  # Enter PHP container shell
```

### Running Tests

```bash
make test              # Run all tests (fail-fast)
make test-coverage     # Run with coverage report
make coverage-html     # Generate HTML coverage
```

### Code Quality

```bash
make lint              # Run all linters (PHPStan + CS Check)
make phpstan           # Static analysis only
make cs-check          # Code style check only
make cs-fix            # Auto-fix code style
```

### Database Operations

```bash
make db-migrate        # Run migrations
make db-rollback       # Rollback last migration
make db-seed           # Load fixtures
make db-console        # Connect to PostgreSQL
```

### Docker Management

```bash
make up                # Start containers (detached)
make down              # Stop containers
make logs              # Follow logs
make build             # Rebuild images
make rebuild           # Rebuild without cache
```

## Project Structure

```
pragmatic-franken/
├── src/
│   ├── {Module}/
│   │   ├── Entity/             # Doctrine Entities
│   │   ├── Enums/
│   │   └── Features/{FeatureName}/   # Feature Slice
│   │       ├── {FeatureName}Command.php
│   │       ├── {FeatureName}Handler.php
│   │       ├── EntryPoint/Http/{FeatureName}Controller.php
│   │       ├── Request/
│   │       └── Response/
│   └── Shared/                 # Cross-cutting concerns
│       ├── Exception/
│       └── Services/
├── config/              # Symfony configuration
├── docs/
│   ├── adr/             # Architectural Decision Records
│   └── guides/         # How-to guides
├── tests/
│   ├── Unit/           # Handler tests
│   ├── Integration/    # Persistence tests
│   └── EndToEnd/       # Controller tests
├── docker/
├── Makefile
└── composer.json
```
pragmatic-franken/
├── src/
│   ├── {Module}/
│   │   ├── Domain/       # Entities, Value Objects, Events
│   │   ├── Application/  # Commands, Queries, Handlers
│   │   ├── Infrastructure/ # Doctrine, External Services
│   │   └── UI/           # Controllers, Commands
│   └── Shared/           # Cross-cutting concerns
├── config/              # Symfony configuration
├── docs/
│   ├── architecture/    # ADR and architecture docs
│   └── guides/          # How-to guides
├── tests/
│   ├── Unit/            # Domain logic tests
│   ├── Integration/    # Handler integration tests
│   └── UI/              # Controller/E2E tests
├── docker/
│   ├── frankenphp/      # FrankenPHP config
│   ├── php/            # PHP extensions config
│   └── ...
├── Makefile
├── docker-compose.yml
└── composer.json
```

## Creating a New Feature

### Step 1: Create Features Structure

```bash
# Create Features directories
mkdir -p src/Task/Features/CreateTask/{EntryPoint/Http,Request,Response}
```

### Step 2: Define Command

```php
// src/Task/Features/CreateTask/CreateTaskCommand.php
declare(strict_types=1);

namespace App\Task\Features\CreateTask;

final readonly class CreateTaskCommand
{
    public function __construct(
        public string $title,
        public int $columnId,
        public ?string $description = null,
    ) {}
}
```

### Step 3: Create Handler

```php
// src/Task/Features/CreateTask/CreateTaskHandler.php
declare(strict_types=1);

namespace App\Task\Features\CreateTask;

use App\Task\Entity\Task;
use App\Task\Features\CreateTask\Request\CreateTaskRequest;
use App\Task\Features\CreateTask\Response\CreateTaskResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateTaskHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function handle(CreateTaskCommand $command): CreateTaskResponse
    {
        $task = new Task($command->title, $command->columnId);
        $this->em->persist($task);
        $this->em->flush();

        return CreateTaskResponse::fromEntity($task);
    }
}
```

### Step 4: Create Request DTO with Validation

```php
// src/Task/Features/CreateTask/Request/CreateTaskRequest.php
declare(strict_types=1);

namespace App\Task\Features\CreateTask\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Create task request")]
final readonly class CreateTaskRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        #[OA\Property(example: "Fix login bug")]
        public string $title,

        #[Assert\Positive]
        #[OA\Property(example: 1)]
        public int $columnId,

        #[Assert\Length(max: 5000)]
        #[OA\Property(nullable: true)]
        public ?string $description = null,
    ) {}
}
```

### Step 5: Create Controller

```php
// src/Task/Features/CreateTask/EntryPoint/Http/CreateTaskController.php
declare(strict_types=1);

namespace App\Task\Features\CreateTask\EntryPoint\Http;

use App\Task\Features\CreateTask\CreateTaskCommand;
use App\Task\Features\CreateTask\Request\CreateTaskRequest;
use App\Task\Features\CreateTask\Response\CreateTaskResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateTaskController
{
    #[Route('/api/tasks', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateTaskRequest $request,
        MessageBusInterface $bus,
    ): CreateTaskResponse {
        $command = new CreateTaskCommand(
            $request->title,
            $request->columnId,
            $request->description,
        );
        return $bus->dispatch($command);
    }
}
```

### Step 6: Write Tests

```php
// tests/Unit/Task/Features/CreateTask/CreateTaskHandlerTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Task\Features\CreateTask;

use App\Task\Features\CreateTask\CreateTaskHandler;
use App\Task\Features\CreateTask\CreateTaskCommand;
use PHPUnit\Framework\TestCase;

final class CreateTaskHandlerTest extends TestCase
{
    public function test_creates_task(): void
    {
        $handler = new CreateTaskHandler($this->em);
        $command = new CreateTaskCommand('Test Task', 1);

        $response = $handler->handle($command);

        self::assertNotNull($response->id);
    }
}
```

## Common Patterns

### Validation with Attributes

```php
declare(strict_types=1);

namespace App\Task\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTaskCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public string $title,
        
        #[Assert\Positive]
        public ?int $projectId = null
    ) {}
}
```

### Using Enums for Status

```php
// src/Task/Domain/TaskStatus.php
declare(strict_types=1);

namespace App\Task\Domain;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';
    
    public function canTransitionTo(TaskStatus $target): bool
    {
        return match ($this) {
            self::TODO => $target === self::IN_PROGRESS,
            self::IN_PROGRESS => $target === self::DONE,
            self::DONE => false,
        };
    }
}
```

### Fetching Related Data with Queries

```php
// For reading, use Query Bus - never expose entities directly
final readonly class GetTaskDetailsHandler
{
    public function handle(GetTaskDetailsQuery $query): TaskDetailsDto
    {
        $task = $this->repository->findById($query->taskId);
        $assignee = $this->userRepository->findById($task->assigneeId);
        $comments = $this->commentRepository->findByTaskId($task->id);
        
        return new TaskDetailsDto(
            id: $task->id,
            title: $task->title,
            status: $task->status,
            assignee: $assignee->name,
            comments: array_map(fn($c) => $c->text, $comments)
        );
    }
}
```

## Debugging Tips

### View Logs

```bash
make logs
```

### Xdebug

Configure your IDE:
- Port: `9003`
- Host: `host.docker.internal` (or `docker.for.mac.localhost` on Mac)
- Start listening for debug connections

### Messenger Inspector

```bash
# View failed messages
php bin/console messenger:failed

# Retry failed messages
php bin/console messenger:retry
```

## Performance Tips

1. **Use Redis for sessions and cache**
2. **Enable OPcache in development** (`make composer-chown`)
3. **Use PHP 8.5 optimizations**
4. **Configure Caddy for HTTP/3**
5. **Use connection pooling in production**

## Code Style Enforcement

This project uses:
- **PHP-CS-Fixer** with PSR-12 rules
- **PHPStan** at level 8 (strict)
- **Rector** for automated upgrades (optional)

Run checks before committing:

```bash
make cs-fix && make phpstan && make test
```
