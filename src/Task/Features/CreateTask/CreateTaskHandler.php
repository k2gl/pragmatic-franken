<?php

declare(strict_types=1);

namespace App\Task\Features\CreateTask;

use App\Board\Entity\Column;
use App\Task\Entity\Task;
use App\Task\Features\CreateTask\CreateTaskMessage;
use App\Task\Features\CreateTask\TaskCreatedResponse;
use App\Task\Repositories\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateTaskHandler
{
    private const FRACTIONAL_STEP = 1000.0;

    public function __construct(
        private EntityManagerInterface $em,
        private TaskRepository $taskRepository
    ) {}

    public function handle(CreateTaskMessage $message): TaskCreatedResponse
    {
        $owner = $message->user;
        if ($owner === null) {
            throw new \RuntimeException('User not set on CreateTaskMessage');
        }

        $column = $this->findColumn($message->columnId);
        $maxPosition = $this->taskRepository->getMaxPosition($message->columnId);
        $newPosition = $this->calculateNewPosition($maxPosition);

        $task = new Task(
            title: $message->title,
            column: $column,
            owner: $owner,
            description: $message->description
        );

        $task->setPosition($newPosition);

        if (!empty($message->tags)) {
            $task->setMetadata(['tags' => $message->tags]);
        }

        $this->em->persist($task);
        $this->em->flush();

        return TaskCreatedResponse::fromEntity($task);
    }

    private function findColumn(int $columnId): Column
    {
        $column = $this->em->find(Column::class, $columnId);

        if ($column === null) {
            throw new \DomainException("Column with ID {$columnId} not found");
        }

        return $column;
    }

    private function calculateNewPosition(float $maxPosition): string
    {
        $newPosition = $maxPosition + self::FRACTIONAL_STEP;
        return number_format($newPosition, 10, '.', '');
    }
}
