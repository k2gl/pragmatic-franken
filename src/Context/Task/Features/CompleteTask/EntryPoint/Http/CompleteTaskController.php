<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CompleteTask\EntryPoint\Http;

use App\Context\Task\Features\CompleteTask\Application\Message\CompleteTaskCommand;
use App\Context\Task\Features\CompleteTask\Application\Dto\CompleteTaskResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CompleteTaskController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/tasks/{taskId}/complete', name: 'app_task_complete', methods: ['POST'])]
    public function __invoke(string $taskId): JsonResponse
    {
        /** @var CompleteTaskResult $result */
        $result = $this->handle(new CompleteTaskCommand($taskId));

        return new JsonResponse(['data' => $result]);
    }
}
