<?php

declare(strict_types=1);

namespace App\Board\UseCase\CreateBoard\Handler;

use App\Board\Entity\Board;
use App\Board\Entity\Column;
use App\Board\UseCase\CreateBoard\Input\CreateBoardMessage;
use App\Board\UseCase\CreateBoard\Output\BoardCreatedResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateBoardHandler
{
    private const FRACTIONAL_STEP = 1000;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function handle(CreateBoardMessage $message): BoardCreatedResponse
    {
        $owner = $message->user;
        if ($owner === null) {
            throw new \RuntimeException('User not set on CreateBoardMessage');
        }

        $board = new Board($message->title, $owner);

        foreach ($message->columns as $index => $columnInput) {
            $position = ($index + 1) * self::FRACTIONAL_STEP;
            $column = new Column($columnInput->name, (string) $position);
            $board->addColumn($column);
        }

        $this->entityManager->persist($board);
        $this->entityManager->flush();

        return new BoardCreatedResponse(
            $board->getId(),
            $board->getUuid(),
            $board->getTitle()
        );
    }
}
