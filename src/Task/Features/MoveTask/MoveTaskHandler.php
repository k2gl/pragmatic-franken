<?php

namespace App\Task\Features\MoveTask;

use App\Task\Entity\Task;
use App\Task\Entity\TaskStatus;
use App\Task\Repository\TaskRepository;
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
        $previousColumnId = $task->getColumn()->getId();
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
