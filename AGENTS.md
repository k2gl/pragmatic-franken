# AI Agent Instructions (Architecture Enforcement)

**Version:** 2.0  
**Last Updated:** 2026-02-04  
**Applies To:** All AI assistants, code reviewers, and automated tools

---

## 🎯 Your Role

You are the lead architect and developer of the **Kanban Pragmatic Architecture** project. Your task is to write code strictly according to **Pragmatic Vertical Slice Architecture**.

You must follow these rules for ALL code changes, documentation, or refactoring.

---

## 🛡 Tech Stack

| Component | Version | Notes |
|-----------|---------|-------|
| PHP | **8.5** | **Required** - use PHP 8.5 features |
| FrankenPHP | 1.x | Application server with Worker Mode |
| Symfony | 7.2 | Framework |
| PostgreSQL | 16 | Primary database |
| Redis | 7 | Cache & Messenger transport |
| Doctrine ORM | 3.3 | Database layer |
| Docker | Latest | Containerization |

### ⚠️ Important: Composer and PHP

**ALWAYS run composer inside Docker:**
```bash
UID=1000 GID=1000 docker compose exec frankenphp composer install
```

**ALWAYS run tests inside Docker:**
```bash
UID=1000 GID=1000 docker compose exec frankenphp ./vendor/bin/phpunit
```

**ALWAYS use make shell to access the container:**
```bash
make shell
```

**FORBIDDEN:**
- Running `composer` from the host machine directly
- Running tests from the host machine
- Running `docker compose` without UID/GID

---

## 🏗 Architecture

### 1. Vertical Slice Architecture (Required!)

**ALWAYS** create a new folder for each feature:
```
src/[Module]/Features/[FeatureName]/
```

**FORBIDDEN:**
- Spreading feature code across global folders (`Services/`, `DTO/`, `Controllers/`)
- Creating common "utils" files without module binding
- Using global services without necessity

**FEATURE STRUCTURE:**
```
src/Task/Features/CreateTask/
├── CreateTaskAction.php       # Controller with #[Route]
├── CreateTaskMessage.php      # DTO + #[Assert] + #[OA\Property]
├── CreateTaskHandler.php      # Business logic
└── CreateTaskResponse.php    # Response (if needed)
```

### 2. #[MapRequestPayload] (Required)

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class CreateTaskAction extends AbstractController
{
    #[Route('/api/tasks', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateTaskMessage $message,
        CreateTaskHandler $handler
    ): TaskResponse {
        return $handler->handle($message);
    }
}
```

### 3. Doctrine ORM (Pragmatic)

**Allowed** to use EntityManager directly:
```php
readonly class ReorderTasksHandler
{
    public function __construct(private EntityManagerInterface $em) {}

    public function handle(ReorderTasksMessage $message): void
    {
        $connection = $this->em->getConnection();
        foreach ($message->orderedIds as $position => $id) {
            $connection->executeStatement(
                'UPDATE tasks SET position = ? WHERE id = ?',
                [$position, $id]
            );
        }
    }
}
```

---

## 📝 Code Rules

### 1. DTO and Attributes

**EVERY** Message.php **MUST** contain attributes:
```php
#[OA\Schema(description: "Request to create a new task")]
final readonly class CreateTaskMessage
{
    public function __construct(
        #[Assert\NotBlank]
        #[OA\Property(example: "Fix login bug")]
        public string $title,

        #[Assert\Positive]
        #[OA\Property(example: 1)]
        public int $columnId,

        /** @var array<string> */
        #[Assert\All([new Assert.Type('string')])]
        #[OA\Property(type: "array", items: new OA\Items(type: "string"))]
        public array $tags = []
    ) {}
}
```

### 2. readonly Classes (Required!)

**ALL** DTO, Message, Response **MUST** be readonly:
```php
#[OA\Schema(description: "Task response")]
final readonly class TaskResponse
{
    public function __construct(
        public int $id,
        public string $title,
        public string $status
    ) {}
}
```

### 3. Entities - Business Logic

```php
#[ORM\Entity]
class Task
{
    #[ORM\Column(type: 'string', enumType: TaskStatus::class)]
    private TaskStatus $status;

    public function move(TaskStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Invalid transition from {$this->status->value} to {$newStatus->value}"
            );
        }
        $this->status = $newStatus;
    }
}
```

---

## 🔄 Communication

### Messenger
```php
use Symfony\Component\Messenger\Attributes\AsMessageHandler;

#[AsMessageHandler]
final readonly class TaskCompletedEvent
{
    public function __construct(
        public int $taskId,
        public string $title
    ) {}
}
```

### Mercure (Real-time)
```php
readonly class CreateTaskHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ?HubInterface $hub = null
    ) {}

    public function handle(CreateTaskMessage $message): TaskResponse
    {
        // ... task creation ...
        
        if ($this->hub !== null) {
            $update = new Update(
                "https://your-kanban.com/board/{$boardId}",
                json_encode(['event' => 'task_created', 'task' => [...]])
            );
            $this->hub->publish($update);
        }
        
        return TaskResponse::fromEntity($task);
    }
}
```

---

## 🗄️ Database

### Fractional Indexing (DECIMAL for Drag&Drop)
```sql
CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    position DECIMAL(20, 10) NOT NULL DEFAULT 0,
    column_id INTEGER NOT NULL REFERENCES board_columns(id)
);

CREATE INDEX idx_tasks_position ON tasks(column_id, position);
```

### JSONB for Metadata
```php
$task->setMetadata([
    'tags' => ['bug', 'high-priority'],
    'color' => '#ff6b6b',
    'checklist' => [['item' => 'Review code', 'done' => true]]
]);
```

---

## 🐳 Docker Commands

### Development
```bash
# Start services
docker compose up -d

# Install dependencies (ALWAYS inside Docker!)
docker compose exec frankenphp composer install

# Run tests
docker compose exec frankenphp ./vendor/bin/phpunit

# Clear cache
docker compose exec frankenphp php bin/console cache:clear

# Run migrations
docker compose exec frankenphp php bin/console doctrine:migrations:migrate
```

### Production Build
```bash
# Build production image
docker build -t kanban-app:latest .

# Multi-stage build targets
docker build --target php_dev -t kanban-app:dev .
docker build --target php_prod -t kanban-app:prod .
```

---

## 📦 Composer.json Requirements

```json
{
    "require": {
        "php": ">=8.5",
        "symfony/framework-bundle": "^7.2",
        "symfony/messenger": "^7.2",
        "symfony/doctrine-bridge": "^7.2",
        "doctrine/orm": "^3.3",
        "predis/predis": "^2.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "zenstruck/messenger-test": "^1.6",
        "dama/doctrine-test-bundle": "^8.2",
        "phpstan/phpstan": "^2.1"
    }
}
```

---

## 🧪 Testing

### PHPUnit
```bash
docker compose exec frankenphp ./vendor/bin/phpunit --fail-fast
```

### Vitest (Frontend)
```bash
npm test
```

### GitHub Actions CI

**View CI logs:**
```bash
# View last failed run
gh run list --limit 1 --status failure

# View logs for specific run
gh run view <run-id> --log

# Link to run
gh run list
```

---

## 🚀 Deployment

### Docker Build
```bash
# Production
docker build -t kanban-app:latest .

# Multi-stage build targets
docker build --target php_dev -t kanban-app:dev .
docker build --target php_prod -t kanban-app:prod .
```

### Zero-Downtime Deploy
```bash
#!/bin/bash
set -e

git pull origin main
docker compose build --pull php
docker compose run --rm php bin/console doctrine:migrations:migrate --no-interaction
docker compose up -d --no-deps php
```

---

## ⚠️ What NOT to Do

| ❌ Forbidden | ✅ Correct |
|-------------|-------------|
| Return Entity from controller | ResponseDTO |
| Repository interfaces without necessity | ServiceEntityRepository |
| static properties | readonly stateless classes |
| Spreading features across modules | One folder = one feature |
| Forgetting `#[OA\Property]` | Every Message with attributes |
| Magic numbers | Constants or Value Objects |

---

## 📁 Project Structure

```
├── src/
│   ├── Kernel.php              # Symfony MicroKernel
│   ├── Task/Features/          # Vertical Slices
│   ├── Board/Features/          # Vertical Slices
│   └── User/                   # Authentication
├── public/
│   ├── js/                     # Vue.js frontend
│   │   ├── components/
│   │   ├── stores/             # Pinia stores
│   │   └── kanban-realtime.js   # Mercure SSE
│   └── index.php
├── docker/
│   ├── php/                    # PHP configs (xdebug, optimizations)
│   ├── prometheus/             # Monitoring
│   └── grafana/                # Dashboards
├── config/
│   ├── routes.yaml
│   └── packages/
├── tests/                      # PHPUnit tests
├── .github/
│   └── workflows/
│       └── deploy.yml          # CI/CD pipeline
├── Caddyfile                   # FrankenPHP config
├── docker-compose.yml          # Development
├── Dockerfile                 # Multi-stage builds
└── composer.json
```

---

## ✅ Pre-Push Checklist

- [ ] Tests pass (`docker compose exec frankenphp ./vendor/bin/phpunit --fail-fast`)
- [ ] PHPStan shows no errors
- [ ] No `dd()`, `var_dump()`
- [ ] Commit follows format
- [ ] `#[OA\Property]` in all MessageDTOs
- [ ] Entity contains business logic

**Remember:** Code is written once but read hundreds of times. Write for humans.
