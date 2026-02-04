<?php

namespace App\Task\Features;

use App\Task\Entity\Task;
use App\User\Entity\User;
use DAMA\DoctrineTestBundle\DoctrineTestBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Factory\Test\ResetDatabase;
use k2gl\PHPUnitFluentAssertions\FluentAssertions;
use App\Factory\UserFactory;
use App\Factory\BoardFactory;
use App\Factory\TaskFactory;
use App\Factory\ColumnFactory;

class TaskLifecycleTest extends KernelTestCase
{
    use HasBrowser, ResetDatabase, FluentAssertions;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    public function testFullTaskLifecycle(): void
    {
        // Arrange: Создаем тестовые данные через Foundry
        $user = UserFactory::createOne();
        $board = BoardFactory::new()->withColumns(3)->create();
        $column = $board->getColumns()->first();

        // Act: Создаем задачу через API
        $this->browser()
            ->actingAs($user->object())
            ->post('/api/tasks', ['json' => [
                'columnId' => $column->getId(),
                'title' => 'Test Task for Lifecycle',
                'description' => 'This task tests the full lifecycle',
                'tags' => ['test', 'lifecycle'],
            ]])
            ->assertStatus(201);

        // Assert: Проверяем ответ
        $response = $this->browser()->response();
        expect($response->getJsonString('$.id'))->toBeInt()->toBeGreaterThan(0);
        expect($response->getJsonString('$.title'))->toBe('Test Task for Lifecycle');
        expect($response->getJsonString('$.status'))->toBe('backlog');
        expect($response->getJsonString('$.columnId'))->toBe($column->getId());
    }

    public function testCreateTaskWithValidationErrors(): void
    {
        // Arrange
        $user = UserFactory::createOne();
        $board = BoardFactory::new()->withColumns(1)->create();
        $column = $board->getColumns()->first();

        // Act & Assert: Пустой заголовок - ошибка валидации
        $this->browser()
            ->actingAs($user->object())
            ->post('/api/tasks', ['json' => [
                'columnId' => $column->getId(),
                'title' => '',
            ]])
            ->assertStatus(422);

        // Act & Assert: Несуществующая колонка - 404
        $this->browser()
            ->actingAs($user->object())
            ->post('/api/tasks', ['json' => [
                'columnId' => 99999,
                'title' => 'Test Task',
            ]])
            ->assertStatus(404);
    }

    public function testCreateTaskWithTags(): void
    {
        // Arrange
        $user = UserFactory::createOne();
        $board = BoardFactory::new()->withColumns(2)->create();
        $column = $board->getColumns()->first();

        // Act
        $this->browser()
            ->actingAs($user->object())
            ->post('/api/tasks', ['json' => [
                'columnId' => $column->getId(),
                'title' => 'Task with Tags',
                'tags' => ['urgent', 'bug', 'high-priority'],
            ]])
            ->assertStatus(201);

        // Assert
        $response = $this->browser()->response();
        expect($response->getJsonString('$.metadata.tags'))->toBeArray()->toContain('urgent');
        expect($response->getJsonString('$.metadata.tags'))->toContain('bug');
    }

    public function testUnauthorizedAccess(): void
    {
        // Act & Assert: Доступ без аутентификации должен возвращать 401
        $this->browser()
            ->post('/api/tasks', ['json' => [
                'columnId' => 1,
                'title' => 'Unauthorized Task',
            ]])
            ->assertStatus(401);
    }

    public function testReorderTasks(): void
    {
        // Arrange
        $user = UserFactory::createOne();
        $board = BoardFactory::new()->withColumns(2)->create();
        $column = $board->getColumns()->first();

        // Создаем несколько задач
        $task1 = TaskFactory::createOne(['column' => $column, 'title' => 'Task 1']);
        $task2 = TaskFactory::createOne(['column' => $column, 'title' => 'Task 2']);
        $task3 = TaskFactory::createOne(['column' => $column, 'title' => 'Task 3']);

        // Act: Reorder
        $this->browser()
            ->actingAs($user->object())
            ->post('/api/tasks/reorder', ['json' => [
                'columnId' => $column->getId(),
                'orderedIds' => [$task3->getId(), $task1->getId(), $task2->getId()],
                'strategy' => 'bulk',
            ]])
            ->assertStatus(200);
    }
}
