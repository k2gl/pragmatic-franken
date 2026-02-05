# Pragmatic Franken - AI Developer Companion

This project uses structured instructions for AI assistants.

## Quick Commands

| Command | Description |
|---------|-------------|
| `make up` | Start development environment |
| `make install` | Install PHP dependencies |
| `make test` | Run PHPUnit tests |
| `make check` | Run all checks (lint + test) |
| `make ci` | Simulate CI pipeline |
| `make shell` | Access FrankenPHP container |
| `make logs` | Follow container logs |

## Architecture Style: Vertical Slices (VSA) + Pragmatic Symfony

### Folder Structure

src/
├── Shared/                      # Глобальная инфраструктура
│   ├── Exception/
│   └── Services/
│
├── {Module}/
│   ├── Entity/                  # Doctrine Entities
│   ├── Enums/
│   └── Features/{FeatureName}/  # Vertical Slices Feature
│       ├── {FeatureName}Command.php      # Command (Write)
│       ├── {FeatureName}Handler.php      # Handler
│       ├── {FeatureName}Query.php        # Query (Read, optional)
│       ├── EntryPoint/                   # Entry points (optional)
│       │   ├── Http/
│       │   ├── Cli/
│       │   └── Queue/
│       ├── Request/                      # Input validation (optional)
│       └── Response/                     # Response (optional)

### Code Rules

#### 1. No Extra Layers
- НЕ создавай интерфейсы для сервисов, если только одна реализация
- НЕ создавай DTO, если хватает валидации в сущности
- Цепочка: EntryPoint → Command/Query → Handler → Entity

#### 2. Message Bus Pattern (Preferred)
```php
// EntryPoint/Http/{FeatureName}Controller.php
final class {FeatureName}Controller extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] {FeatureName}Request $request,
        MessageBusInterface $bus
    ): {FeatureName}Response {
        $command = new {FeatureName}Command(...);
        return $bus->dispatch($command);
    }
}
```

#### 3. Slim Controller / EntryPoint
EntryPoint только:
- Транслирует входные данные в Command/Query
- Вызывает `$bus->dispatch()`
- Возвращает Response

НИКАКОЙ бизнес-логики в EntryPoint.

#### 4. Attributes in DTOs (Validation + OpenAPI)
```php
// Request/{FeatureName}Request.php
final readonly class {FeatureName}Request
{
    public function __construct(
        #[OA\Property(description: "User email", example: "user@example.com")]
        #[Assert\Email]
        #[Assert\NotBlank]
        public string $email,

        #[Assert\Length(min: 8)]
        public string $password
    ) {}
}
```

#### 5. CQRS Separation

**Command (Write):**
- Возвращает: `id`, `void`, или Domain Event
- Использует: Doctrine Entity
- Логика: бизнес-правила, валидация, persistence

**Query (Read):**
- Возвращает: DTO/Response
- Использует: DBAL, raw SQL, или non-tracked ORM
- Логика: projection, оптимизация чтения

#### 6. Event Communication
Модули общаются ТОЛЬКО через EventBus:
```php
// В Handler
$this->eventBus->dispatch(new {FeatureName}Event($data));
```

### Naming Conventions

| Тип | Пример |
|-----|--------|
| Command | `{FeatureName}Command.php` |
| Query | `{FeatureName}Query.php` |
| Handler | `{FeatureName}Handler.php` |
| EntryPoint HTTP | `{FeatureName}Controller.php` |
| EntryPoint CLI | `{FeatureName}Console.php` |
| Request | `{FeatureName}Request.php` |
| Response | `{FeatureName}Response.php` |

### Pragmatic Rules

#### Interfaces
СОЗДАВАЙ интерфейс ТОЛЬКО если:
- Будет несколько реализаций
- Нужна заглушка для тестов (но лучше `Zenstruck\Messenger\Test`)

#### When to Skip Message Bus
Допустимо прямой вызов сервиса для:
- Простой CRUD без async
- Legacy миграция (постепенное внедрение)
- Performance-critical пути

#### Symfony Power
- Используй `#[MapRequestPayload]` для авто-десериализации
- Атрибуты `#[Route]` вместо YAML-маршрутов
- Autowiring через конструктор

### Code Standards

- **PHP 8.5** with `declare(strict_types=1)`
- **Vertical slices** for features (no technical layers)
- **Attributes** for routing/validation/Doctrine (no XML/YAML)
- **Enums** for status values
- **Message Bus** (Symfony Messenger) for commands/queries

### Common Patterns

#### Command (Write)
```php
// src/{Module}/Features/{FeatureName}/{FeatureName}Command.php
final readonly class {FeatureName}Command
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}

// src/{Module}/Features/{FeatureName}/{FeatureName}Handler.php
#[AsMessageHandler]
readonly class {FeatureName}Handler
{
    public function handle({FeatureName}Command $command): {FeatureName}Response
    {
        // Business logic
    }
}
```

#### Query (Read)
```php
// src/{Module}/Features/{FeatureName}/{FeatureName}Query.php
final readonly class {FeatureName}Query
{
    public function __construct(
        public int $id
    ) {}
}

// src/{Module}/Features/{FeatureName}/{FeatureName}Handler.php
#[AsMessageHandler]
readonly class {FeatureName}Handler
{
    public function handle({FeatureName}Query $query): {FeatureName}Response
    {
        // Read logic - return DTO
    }
}
```

#### EntryPoint (HTTP)
```php
// src/{Module}/Features/{FeatureName}/EntryPoint/Http/{FeatureName}Controller.php
final class {FeatureName}Controller extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] {FeatureName}Request $request,
        MessageBusInterface $bus
    ): {FeatureName}Response {
        $command = new {FeatureName}Command($request->email, $request->password);
        return $bus->dispatch($command);
    }
}
```

### AI Instructions

При создании новой фичи генерируй:
1. `{FeatureName}Command.php` или `{FeatureName}Query.php`
2. `{FeatureName}Handler.php` с бизнес-логикой
3. EntryPoint (`Controller.php` или `Console.php`)
4. `{FeatureName}Request.php` с валидацией
5. `{FeatureName}Response.php` с OpenAPI документацией

### Compliance Checklist

- [ ] Message Bus используется для новых фич?
- [ ] Нет лишних интерфейсов (только одна реализация)?
- [ ] EntryPoint только транслирует в Command/Query?
- [ ] `#[Assert]` и `#[OA]` в одном файле DTO?
- [ ] `#[MapRequestPayload]` для десериализации?
- [ ] Handler использует `#[AsMessageHandler]`?

## Configuration Priority

**Before starting work, load settings from:**

1. **Base Rules**: `.config/agents/agents.md`
2. **Local Settings**: `.config/agents/agents.local.md` (if exists)

Local settings from `.config/agents/` have priority over any other instructions.

## Documentation

- See `docs/adr/` for architecture decisions
- See `docs/architecture/vertical-slices.md` for VSA patterns
- See `docs/guides/` for development guides

## Test Structure

tests/
├── Unit/                              # Domain logic tests
│   └── {Module}/
│       └── Features/
│           └── {FeatureName}/
│               └── {FeatureName}HandlerTest.php
├── Integration/                       # Handler and persistence tests
│   └── {Module}/
│       └── Features/
│           └── {FeatureName}/
│               └── {FeatureName}HandlerTest.php
└── EndToEnd/                          # Controller/E2E tests
    └── {Module}/
        └── Features/
            └── {FeatureName}/
                └── {FeatureName}ControllerTest.php

### Testing Principles

1. **Unit Tests:** Test handlers in isolation with mocked dependencies
2. **Integration Tests:** Test with real database (Zenstruck Foundry)
3. **E2E Tests:** Test full HTTP flow (WebTestCase)

### Testing Standards

- **Naming:** `{FeatureName}HandlerTest.php` for handlers
- **Naming:** `{FeatureName}ControllerTest.php` for controllers
- **Framework:** PHPUnit with Zenstruck\Messenger\Test for async tests
- **Isolation:** Each test is independent, no shared state

### Example: Handler Test

```php
// tests/Unit/User/Features/Login/LoginHandlerTest.php
declare(strict_types=1);

namespace App\Tests\Unit\User\Features\Login;

use App\User\Features\Login\LoginHandler;
use App\User\Features\Login\LoginCommand;
use App\User\Features\Login\LoginResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class LoginHandlerTest extends TestCase
{
    public function test_returns_token_on_valid_credentials(): void
    {
        // Arrange
        $handler = new LoginHandler($this->createMock(UserRepository::class));
        $command = new LoginCommand('user@example.com', 'password123');

        // Act
        $response = $handler->handle($command);

        // Assert
        self::assertInstanceOf(LoginResponse::class, $response);
        self::assertNotEmpty($response->token);
    }
}
```
