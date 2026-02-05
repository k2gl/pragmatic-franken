<?php

declare(strict_types=1);

namespace App\Task\Features\ReorderTasks;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ReorderTasksHandler
{
    public const INSERT_BETWEEN = 'between';
    public const INSERT_AT_TOP = 'at_top';
    public const INSERT_AT_BOTTOM = 'at_bottom';

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function handle(ReorderTasksMessage $message): ReorderTasksResult
    {
        $connection = $this->entityManager->getConnection();

        return match ($message->strategy) {
            self::INSERT_BETWEEN => $this->insertBetween(
                $connection,
                $message->taskId,
                $message->prevTaskId,
                $message->nextTaskId,
                $message->columnId
            ),
            self::INSERT_AT_TOP => $this->insertAtTop($connection, $message->taskId, $message->columnId),
            default => $this->updateOrder($connection, $message->orderedIds, $message->columnId),
        };
    }

    private function insertBetween(
        $connection,
        int $taskId,
        ?int $prevTaskId,
        ?int $nextTaskId,
        int $columnId
    ): ReorderTasksResult {
        $prevPos = $prevTaskId !== null
            ? $this->getTaskPosition($connection, $prevTaskId)
            : -1;

        $nextPos = $nextTaskId !== null
            ? $this->getTaskPosition($connection, $nextTaskId)
            : PHP_FLOAT_MAX;

        $newPos = ($prevPos + $nextPos) / 2;

        $connection->executeStatement(
            'UPDATE tasks SET position = ?, column_id = ?, updated_at = NOW() WHERE id = ?',
            [$newPos, $columnId, $taskId]
        );

        return new ReorderTasksResult($taskId, $newPos, 'inserted_between');
    }

    private function insertAtTop($connection, int $taskId, int $columnId): ReorderTasksResult
    {
        $minPos = $connection->executeQuery(
            'SELECT MIN(position) FROM tasks WHERE column_id = ?',
            [$columnId]
        )->fetchOne();

        $newPos = $minPos - 1;

        $connection->executeStatement(
            'UPDATE tasks SET position = ?, column_id = ?, updated_at = NOW() WHERE id = ?',
            [$newPos, $columnId, $taskId]
        );

        return new ReorderTasksResult($taskId, $newPos, 'inserted_at_top');
    }

    private function updateOrder($connection, array $orderedIds, int $columnId): ReorderTasksResult
    {
        $connection->transactional(function () use ($connection, $orderedIds, $columnId) {
            foreach ($orderedIds as $position => $taskId) {
                $connection->executeStatement(
                    'UPDATE tasks SET position = ?, column_id = ?, updated_at = NOW() WHERE id = ?',
                    [$position, $columnId, $taskId]
                );
            }
        });

        return new ReorderTasksResult(
            $orderedIds[0] ?? 0,
            (float) ($orderedIds[0] ?? 0),
            'bulk_reordered'
        );
    }

    private function getTaskPosition($connection, int $taskId): float
    {
        return (float) $connection->executeQuery(
            'SELECT position FROM tasks WHERE id = ?',
            [$taskId]
        )->fetchOne();
    }
}
