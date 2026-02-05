# ADR 001: Pragmatic Symfony Architecture

## Status

**Accepted**

## Context

When developing with Symfony, teams often face a dilemma between following strict enterprise standards (Hexagonal Architecture, DDD) and delivering features quickly. Excessive abstraction in medium-sized projects often leads to:

- **Over-engineering**: Unnecessary complexity without clear business value
- **Boilerplate code**: Excessive file count slowing down development
- **Slow time-to-market**: High cost per feature
- **Onboarding friction**: New developers struggle to understand complex abstractions

We needed an approach that leverages Symfony's power without becoming "hostage" to the framework, while also avoiding "code purity" that ignores practical benefits.

## Decision

We adopt a **Pragmatic Symfony Architecture** based on these principles:

### 1. Follow Symfony Best Practices

Instead of introducing custom structures or complex patterns (e.g., Hexagonal Architecture in small services), we use official Symfony recommendations and standard directory structure.

### 2. No Extra Layers

- **Don't create interfaces** for services unless multiple implementations are expected
- **Don't use DTOs** where form validation or entities are sufficient
- Standard chain: Controller → Service → Entity

### 3. Symfony Flex & Autowiring

Maximum trust in automatic dependency injection. Avoid manual service registration in YAML/XML where possible.

### 4. Modularity "On Demand"

Use only necessary Symfony components (HttpFoundation, Routing) for microservices instead of full stack bundle when justified by performance requirements.

### 5. Framework Coupling Is Acceptable

We permit using Symfony capabilities (Attributes, Doctrine) directly in business logic to accelerate development.

### 6. Attributes in DTOs (Validation & OpenAPI)

For maximum development speed and clarity, we abandon separate validation and API schema configurations:

- **Validation (Assert)**: Validation rules are defined using Symfony Validator attributes (`#[Assert\...]`) directly in DTO properties
- **API Specification (OpenAPI)**: Swagger/OAS specifications are described using PHP 8 attributes (`#[OA\Property]`, `#[OA\Schema]`) within the same DTOs
- **Single Source of Truth**: DTO becomes the only place defining structure, validation rules, and frontend/client documentation

### 7. Native Symfony Mapping

We use built-in Symfony tools (Serializers or `#[MapRequestPayload]`) for automatic JSON-to-DTO conversion with validation.

## Consequences

### Positive

| Benefit | Description |
|---------|-------------|
| **Time-to-Market** | Significant reduction in feature delivery time |
| **Onboarding** | Lower entry barrier for new developers (standard Symfony code is more understandable than custom abstractions) |
| **Code Reduction** | Simpler maintenance with less code volume |
| **Developer Experience** | Familiar patterns reduce cognitive load |
| **Self-Documenting** | Attributes serve as live documentation |

### Negative / Risks

| Risk | Mitigation |
|------|-------------|
| **Framework Coupling** | Higher effort required if migrating to another framework | Accept as trade-off for development speed |
| **Testing Complexity** | Pure business logic testing without DB is harder | Use WebTestCase for functional tests |
| **DTO "Noise"** | Many attributes can make classes visually cluttered | Keep DTOs focused on single responsibility |

## Comparison

| Characteristic | Pure / Hexagonal (DDD) | Pragmatic Symfony |
|-----------------|--------------------------|------------------|
| **Code** | Framework-independent business logic | Uses Symfony capabilities directly |
| **Complexity** | Many classes: ports, adapters, mappers | Minimal class count |
| **Speed** | Slow start, high feature cost | Maximum speed (Flex, autowiring) |
| **Testing Focus** | Unit tests without environment | Functional tests (WebTestCase) |
| **Validation** | Extracted to Domain/Value Objects | `#[Assert]` attributes in DTOs |
| **API Docs** | Separate YAML/JSON specification files | `#[OA]` attributes in DTOs |

## Code Examples

### Example: Pragmatic Request DTO

```php
declare(strict_types=1);

namespace App\User\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(description: "Request data for user registration")]
final readonly class CreateUserRequest
{
    public function __construct(
        #[OA\Property(description: "Username", example: "john_doe")]
        #[Assert\NotBlank(message: "Username is required")]
        #[Assert\Length(min: 3, max: 50)]
        public readonly string $username,

        #[OA\Property(description: "Email address", example: "john@example.com")]
        #[Assert\NotBlank]
        #[Assert\Email(message: "Invalid email format")]
        public readonly string $email,

        #[OA\Property(description: "User role", example: "ROLE_USER")]
        #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN'], message: "Invalid role")]
        public readonly string $role = 'ROLE_USER',
    ) {}
}
```

### Example: Slim Controller

```php
declare(strict_types=1);

namespace App\User\UI\Http;

use App\User\Application\Dto\CreateUserRequest;
use App\User\Application\Service\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateUserAction
{
    public function __construct(
        private UserService $userService
    ) {}

    #[Route('/api/users', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateUserRequest $dto
    ): JsonResponse {
        $user = $this->userService->create($dto);
        
        return $this->json(['id' => $user->getId()], 201);
    }
}
```

### Example: Simple Service

```php
declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Dto\CreateUserRequest;
use App\User\Application\Dto\UserResponse;
use App\User\Infrastructure\UserRepository;

final readonly class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function create(CreateUserRequest $dto): UserResponse
    {
        $user = User::register($dto->email, $dto->username);
        $this->repository->save($user);
        
        return UserResponse::fromEntity($user);
    }
}
```

### Message Bus Rule

Actions MUST use Message Bus pattern, not direct handler calls.

**Required Pattern:**
```php
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateTaskAction
{
    public function __invoke(
        #[MapRequestPayload] CreateTaskMessage $message,
        MessageBusInterface $bus,
    ): CreateTaskResponse {
        return $bus->dispatch($message);
    }
}
```

**NOT ALLOWED - Direct Handler Call:**
```php
// ❌ WRONG - creates tight coupling
final readonly class CreateTaskAction
{
    public function __invoke(
        #[MapRequestPayload] CreateTaskMessage $message,
        CreateTaskHandler $handler,
    ): CreateTaskResponse {
        return $handler->handle($message);
    }
}
```

### Modular Architecture

Modules follow Vertical Slice pattern with clear boundaries:

```
src/
├── [ModuleName]/                    # Bounded Context
│   ├── Features/
│   │   └── [FeatureName]/
│   │       ├── [FeatureName]Action.php      # Uses Message Bus
│   │       ├── [FeatureName]Message.php    # extends Message
│   │       ├── [FeatureName]Handler.php     # #[AsMessageHandler]
│   │       └── [FeatureName]Response.php    # readonly
│   ├── Entity/
│   ├── Repository/
│   └── ValueObject/
└── Shared/
    ├── Exception/
    └── Interface/
```

**Rules:**
- Modules may import from other modules (light coupling)
- Shared layer contains only truly cross-cutting concerns
- Actions MUST use Message Bus (not direct handler calls)

## Compliance

This ADR is followed when:

1. **Controllers are slim** - Only dispatch requests via Message Bus
2. **No unnecessary interfaces** - Created only when multiple implementations exist
3. **Attributes in DTOs** - Validation and OpenAPI documentation in same file
4. **Native Symfony mapping** - `#[MapRequestPayload]` for automatic deserialization
5. **Functional tests exist** - At least one WebTestCase per feature

---

## Appendix A: ADR 001 Compliance Checklist

### 1. Structure and Layers

- [ ] **Message Bus Required**: Does the action use `MessageBusInterface`? Direct handler calls are NOT allowed.
- [ ] **No unnecessary interfaces**: Does the service have an interface? If only one implementation exists, remove the interface.
- [ ] **Call chain consistency**: Does the logic follow Controller → Message Bus → Handler → Entity?
- [ ] **Slim Controller**: Does the controller only dispatch messages and return responses?

### 2. DTOs and Data Handling

- [ ] **Attributes over configs**: Are validation (`#[Assert]`) and API docs (`#[OA]`) inside DTOs? No duplicate YAML/XML configs?
- [ ] **Native Symfony mapping**: Is `#[MapRequestPayload]` or `MapQueryString` used? If manual mapping exists (foreach, etc.), refactor.
- [ ] **Readonly properties**: Are `public readonly` properties used in DTOs?

### 3. Symfony Power

- [ ] **Autowiring**: Are services injected via constructor automatically?
- [ ] **Attributes over annotations**: Are modern PHP 8 attributes (`#[Route]`) used?

### 4. Testing

- [ ] **Test balance**: For CRUD features, is there at least one functional test (WebTestCase)?

### Quick Reference

| Check | Question |
|-------|-----------|
| Message Bus | Does action use `MessageBusInterface`? |
| Interface needed? | Does this service have multiple implementations? |
| DTO correct? | Are `#[Assert]` and `#[OA]` in the same file? |
| Controller slim? | Does it only call `$bus->dispatch()`? |
| Tests exist? | At least one WebTestCase per feature? |
