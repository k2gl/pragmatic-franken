<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CompleteTask\Application;

use App\Context\Task\Entity\Task;
use App\Context\Task\Features\CompleteTask\Application\Dto\CompleteTaskResult;
use App\Context\Task\Features\CompleteTask\Application\Message\CompleteTaskCommand;
use App\Context\Task\Repository\TaskRepository;
use App\Context\Task\Shared\Events\TaskCompleted;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use DateTimeImmutable;
use DateTimeInterface;

#[AsMessageHandler]
final readonly class CompleteTaskHandler
{
    public function __construct(
        private TaskRepository $tasks,
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CompleteTaskCommand $command): CompleteTaskResult
    {
        // Throws EntityNotFoundException → 404 problem+json (SharedKernel listener).
        $task = $this->tasks->get($command->taskId);

        if (! $task->completed) {
            $task->complete();
            $this->tasks->save($task);

            $this->eventBus->dispatch(new TaskCompleted(
                taskId: (string) $task->id,
                title: $task->title->value,
                completedAt: $this->completedAtOf($task),
            ));
        }

        return new CompleteTaskResult(
            id: (string) $task->id,
            title: $task->title->value,
            completed: true,
            completedAt: $this->completedAtOf($task)->format(DateTimeInterface::ATOM),
        );
    }

    private function completedAtOf(Task $task): DateTimeImmutable
    {
        $completedAt = $task->completedAt;
        \assert($completedAt !== null);

        return $completedAt;
    }
}
