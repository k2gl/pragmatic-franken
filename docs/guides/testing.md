# Testing Strategy

## Overview

This project follows a layered testing strategy matching the application architecture. Each layer has specific testing approaches.

## Testing Pyramid

```
        /\
       /  \        E2E Tests
      /____\       (Few - slow)
     /      \
    /________\      Integration Tests
   /          \     (Some - medium)
  /____________\
 /              \
/________________\ Unit Tests
(Many - fast)
```

## Test Structure

```
tests/
├── Unit/                              # Handler tests (isolated)
│   └── {Module}/
│       └── Features/
│           └── {FeatureName}/
│               └── {FeatureName}HandlerTest.php
├── Integration/                       # Handler + persistence tests
│   └── {Module}/
│       └── Features/
│           └── {FeatureName}/
│               └── {FeatureName}HandlerTest.php
└── EndToEnd/                          # Controller tests
    └── {Module}/
        └── Features/
            └── {FeatureName}/
                └── {FeatureName}ControllerTest.php
```

## 1. Unit Tests (Handler Layer)

**Purpose:** Test handlers in isolation with mocked dependencies

**Scope:** Single handler, mocked repositories

**Tools:** PHPUnit, mock libraries

### Handler Testing

```php
declare(strict_types=1);

namespace App\Tests\Unit\User\Features\Login;

use App\User\Features\Login\LoginHandler;
use App\User\Features\Login\LoginCommand;
use App\User\Features\Login\LoginResponse;
use PHPUnit\Framework\TestCase;

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
    
    public function test_activates_user_successfully(): void
    {
        $user = new User(1, 'test@example.com', 'John Doe');
        
        $user->activate();
        
        self::assertSame(UserStatus::ACTIVE, $user->status);
    }
    
    public function test_cannot_activate_already_active_user(): void
    {
        $user = new User(1, 'test@example.com', 'John Doe');
        $user->activate();
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User cannot be activated');
        
        $user->activate();
    }
    
    public function test_records_domain_event_on_registration(): void
    {
        $user = new User(1, 'test@example.com', 'John Doe');
        $events = $user->releaseEvents();
        
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegisteredEvent::class, $events[0]);
    }
}
```

### Value Object Testing

```php
declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_creates_valid_email(): void
    {
        $email = new Email('user@example.com');
        
        self::assertSame('user@example.com', $email->value);
    }
    
    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Email('not-an-email');
    }
    
    public function test_emails_are_case_insensitive(): void
    {
        $email1 = new Email('User@Example.com');
        $email2 = new Email('user@example.com');
        
        self::assertTrue($email1->equals($email2));
    }
}
```

## 2. Integration Tests (Application Layer)

**Purpose:** Test handlers with real infrastructure

**Scope:** Handler + Repository + Database

**Tools:** PHPUnit, Doctrine Test Bundle, In-Memory Database

### Handler Testing

```php
declare(strict_types=1);

namespace App\Tests\Integration\User\Application\Handler;

use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Handler\RegisterUserHandler;
use App\User\Infrastructure\TestUserRepository;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private RegisterUserHandler $handler;
    private TestUserRepository $repository;
    
    protected function setUp(): void
    {
        $this->repository = new TestUserRepository();
        $this->handler = new RegisterUserHandler($this->repository);
    }
    
    public function test_creates_user_and_saves_to_repository(): void
    {
        $command = new RegisterUserCommand(
            'test@example.com',
            'securepassword',
            'John Doe'
        );
        
        $this->handler->handle($command);
        
        $savedUser = $this->repository->findByEmail('test@example.com');
        self::assertNotNull($savedUser);
        self::assertSame('John Doe', $savedUser->name);
    }
}
```

### Using Doctrine Test Bundle

```php
declare(strict_types=1);

namespace App\Tests\Integration\User\Handler;

use App\User\Application\Command\CreateOrderCommand;
use App\User\Infrastructure\Doctrine\OrderRepository;
use Dama\DoctrineTestBundle\Doctrine\TestEntityManager;
use PHPUnit\Framework\TestCase;

final class CreateOrderHandlerTest extends TestCase
{
    use \Dama\DoctrineTestBundle\PHPUnit\DoctrineTestHelperTrait;
    
    private CreateOrderHandler $handler;
    private TestEntityManager $em;
    
    protected function setUp(): void
    {
        $this->em = $this->createEntityManager();
        $repository = new OrderRepository($this->em->getConnection());
        $this->handler = new CreateOrderHandler($repository);
    }
    
    public function test_creates_order_with_items(): void
    {
        $command = new CreateOrderCommand(
            userId: 1,
            items: [
                ['productId' => 1, 'quantity' => 2],
                ['productId' => 2, 'quantity' => 1],
            ]
        );
        
        $order = $this->handler->handle($command);
        
        self::assertNotNull($order->id);
        self::assertCount(2, $order->items);
        self::assertSame(OrderStatus::PENDING, $order->status);
    }
}
```

## 3. EndToEnd Tests (Controller Layer)

**Purpose:** Test HTTP endpoints

**Tools:** PHPUnit, Symfony WebTestCase

### Controller Testing

```php
declare(strict_types=1);

namespace App\Tests\EndToEnd\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateUserControllerTest extends WebTestCase
{
    public function test_creates_user_and_returns_201(): void
    {
        $client = self::createClient();
        
        $client->request(
            'POST',
            '/api/users',
            content: json_encode([
                'email' => 'test@example.com',
                'password' => 'securepassword',
                'name' => 'John Doe'
            ])
        );
        
        self::assertResponseStatusCodeSame(201);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $response);
        self::assertSame('test@example.com', $response['email']);
    }
    
    public function test_returns_400_for_invalid_input(): void
    {
        $client = self::createClient();
        
        $client->request(
            'POST',
            '/api/users',
            content: json_encode([
                'email' => 'not-an-email'
            ])
        );
        
        self::assertResponseStatusCodeSame(400);
    }
}
```

### Kernel Test for Configuration

```php
declare(strict_types=1);

namespace App\Tests\UI;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Kernel\KernelInterface;

final class KernelTest extends TestCase
{
    public function test_kernel_boots_successfully(): void
    {
        $kernel = self::createKernel();
        $kernel->boot();
        
        self::assertInstanceOf(KernelInterface::class, $kernel->getContainer());
    }
}
```

## 4. Event Handler Testing

**Purpose:** Test async event handlers

```php
declare(strict_types=1);

namespace App\Tests\Integration\User\Event;

use App\User\Application\Handler\SendWelcomeEmailHandler;
use App\User\Application\Dto\UserResponse;
use App\User\Domain\Event\UserRegisteredEvent;
use App\Shared\Infrastructure\Email\TestEmailSender;
use PHPUnit\Framework\TestCase;

final class SendWelcomeEmailHandlerTest extends TestCase
{
    public function test_sends_welcome_email_on_registration(): void
    {
        $emailSender = new TestEmailSender();
        $handler = new SendWelcomeEmailHandler($emailSender);
        
        $event = new UserRegisteredEvent(
            userId: 1,
            email: 'test@example.com'
        );
        
        $handler->handle($event);
        
        self::assertTrue($emailSender->wasCalledWith(
            'test@example',
            '[Welcome] Welcome to our platform!'
        ));
    }
}
```

## Test Fixtures

### Using DoctrineFixturesBundle

```php
declare(strict_types=1);

namespace App\DataFixtures;

use App\User\Domain\User;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User(1, new Email('admin@example.com'), 'Admin User');
        $user->activate();
        $manager->persist($user);
        
        $manager->flush();
    }
}
```

## Mocking Strategies

### When to Use Mocks

1. **Unit Tests**: Mock all external dependencies
2. **Integration Tests**: Use real implementations or in-memory fakes
3. **Never Mock**: Entities (test them directly)

### Example: Mocking Repository

```php
declare(strict_types=1);

namespace App\Tests\Unit\User\Handler;

use App\User\Application\Command\ActivateUserCommand;
use App\User\Application\Handler\ActivateUserHandler;
use App\User\Infrastructure\TestUserRepository;
use PHPUnit\Framework\TestCase;

final class ActivateUserHandlerTest extends TestCase
{
    public function test_activates_user(): void
    {
        $repository = new TestUserRepository();
        $repository->save(new User(1, 'test@example.com', 'John'));
        
        $handler = new ActivateUserHandler($repository);
        $handler->handle(new ActivateUserCommand(1));
        
        $user = $repository->findById(1);
        self::assertTrue($user->isActive());
    }
}
```

## Coverage Requirements

| Layer | Minimum Coverage |
|-------|------------------|
| Domain | 90% |
| Application | 80% |
| Infrastructure | 60% |
| UI | 40% |

## Running Tests

```bash
# Run all tests
make test

# Run with coverage
make test-coverage

# Generate HTML coverage report
make coverage-html

# Run specific test file
./vendor/bin/phpunit tests/Unit/User/Domain/UserTest.php
```

## Continuous Integration

Tests run automatically on CI with:

1. **Unit Tests** - Fast, parallelized
2. **Integration Tests** - With test database
3. **Code Quality** - PHPStan, PHP-CS-Fixer
4. **Mutation Testing** - (Optional) Ensures tests actually test behavior

## Best Practices

1. **Test Behavior, Not Implementation**
2. **Use Descriptive Test Names**
3. **Follow Arrange-Act-Assert Pattern**
4. **Isolate Tests** - No test should depend on another
5. **Keep Tests Fast** - Under 100ms per test
6. **Use Data Providers** for multiple scenarios
7. **Avoid Logic in Tests** - No loops, conditionals in tests
8. **Test Edge Cases** - Null, empty, boundary values
