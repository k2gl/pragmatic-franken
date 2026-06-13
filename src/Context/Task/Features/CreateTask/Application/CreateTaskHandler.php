<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\Application;

use App\Context\Task\Entity\Task;
use App\Context\Task\Features\CreateTask\Application\Dto\CreateTaskResult;
use App\Context\Task\Features\CreateTask\Application\Message\CreateTaskCommand;
use App\Context\Task\Repository\TaskRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use DateTimeInterface;

#[AsMessageHandler]
final readonly class CreateTaskHandler
{
    public function __construct(
        private TaskRepository $tasks,
    ) {}

    public function __invoke(CreateTaskCommand $command): CreateTaskResult
    {
        $task = Task::create($command->title);
        $this->tasks->save($task);

        return new CreateTaskResult(
            id: (string) $task->id,
            title: $task->title->value,
            completed: $task->completed,
            createdAt: $task->createdAt->format(DateTimeInterface::ATOM),
        );
    }
}
