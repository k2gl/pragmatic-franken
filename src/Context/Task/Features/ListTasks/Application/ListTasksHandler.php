<?php

declare(strict_types=1);

namespace App\Context\Task\Features\ListTasks\Application;

use App\Context\Task\Features\ListTasks\Application\Dto\ListTasksResult;
use App\Context\Task\Features\ListTasks\Application\Dto\TaskItem;
use App\Context\Task\Features\ListTasks\Application\Message\ListTasksQuery;
use App\Context\Task\Repository\TaskRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use DateTimeInterface;

#[AsMessageHandler]
final readonly class ListTasksHandler
{
    public function __construct(
        private TaskRepository $tasks,
    ) {}

    public function __invoke(ListTasksQuery $query): ListTasksResult
    {
        $items = [];

        foreach ($this->tasks->findNewestFirst() as $task) {
            $items[] = new TaskItem(
                id: (string) $task->id,
                title: $task->title->value,
                completed: $task->completed,
                createdAt: $task->createdAt->format(DateTimeInterface::ATOM),
            );
        }

        return new ListTasksResult($items);
    }
}
