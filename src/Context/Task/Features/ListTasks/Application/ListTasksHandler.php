<?php

declare(strict_types=1);

namespace App\Context\Task\Features\ListTasks\Application;

use App\Context\Task\Repository\TaskRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ListTasksHandler
{
    public function __construct(
        private TaskRepository $tasks,
    ) {
    }

    public function __invoke(ListTasksQuery $query): ListTasksResult
    {
        $items = [];

        foreach ($this->tasks->findNewestFirst() as $task) {
            $items[] = new TaskItem(
                id: (string) $task->id(),
                title: $task->title(),
                completed: $task->isCompleted(),
                createdAt: $task->createdAt()->format(\DateTimeInterface::ATOM),
            );
        }

        return new ListTasksResult($items);
    }
}
