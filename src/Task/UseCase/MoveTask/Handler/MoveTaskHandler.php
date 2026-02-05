<?php

declare(strict_types=1);

namespace App\Task\UseCase\MoveTask\Handler;

use App\Task\Entity\Task;
use App\Task\UseCase\MoveTask\Input\MoveTaskMessage;
use App\Task\UseCase\MoveTask\Output\MoveTaskResult;
use App\Task\UseCase\CreateTask\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class MoveTaskHandler
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function handle(MoveTaskMessage $message): MoveTaskResult
    {
        $task = $this->taskRepository->find($message->taskId)
            ?? throw new \DomainException("Task {$message->taskId} not found");

        $previousStatus = $task->getStatus()->value;
        $task->move($message->newStatus);

        $this->entityManager->flush();

        return new MoveTaskResult(
            taskId: $task->getId(),
            title: $task->getTitle(),
            previousStatus: $previousStatus,
            newStatus: $message->newStatus->value,
            updatedAt: $task->getUpdatedAt()
        );
    }
}
