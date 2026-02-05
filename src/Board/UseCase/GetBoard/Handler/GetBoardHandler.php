<?php

declare(strict_types=1);

namespace App\Board\UseCase\GetBoard\Handler;

use App\Board\Entity\Board;
use App\Board\UseCase\GetBoard\Input\GetBoardQuery;
use App\Board\UseCase\GetBoard\Output\BoardResponse;
use App\Board\UseCase\GetBoard\Output\ColumnDTO;
use App\Board\UseCase\GetBoard\Output\TaskDTO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetBoardHandler
{
    public function __construct(
        private BoardRepository $repository
    ) {}

    public function handle(GetBoardQuery $query): BoardResponse
    {
        $user = $query->user;
        if ($user === null) {
            throw new \RuntimeException('User not set on GetBoardQuery');
        }

        $board = $this->repository->findFullBoard($query->boardId, $user);

        if (!$board) {
            throw new NotFoundHttpException('Board not found or access denied');
        }

        return $this->mapToResponse($board);
    }

    private function mapToResponse(Board $board): BoardResponse
    {
        $columns = array_map(fn($column) => new ColumnDTO(
            $column->getId(),
            $column->getName(),
            (float) $column->getPosition(),
            array_map(fn($task) => new TaskDTO(
                $task->getId(),
                $task->getUuid(),
                $task->getTitle(),
                (float) $task->getPosition(),
                $task->getStatus()->value,
                $task->getDescription(),
                $task->getAssignee()?->getId(),
                $task->getDueDate()?->format('c'),
                $task->getMetadata(),
                $task->getCreatedAt()->format('c')
            ), $column->getTasks()->toArray()),
            $column->getTasks()->count()
        ), $board->getColumns()->toArray());

        return new BoardResponse(
            $board->getId(),
            $board->getTitle(),
            $board->getUuid(),
            $columns,
            $board->getSettings()
        );
    }
}
