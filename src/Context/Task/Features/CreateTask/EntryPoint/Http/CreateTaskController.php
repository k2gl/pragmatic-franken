<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\EntryPoint\Http;

use App\Context\Task\Features\CreateTask\Application\Message\CreateTaskCommand;
use App\Context\Task\Features\CreateTask\Application\Dto\CreateTaskResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CreateTaskController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/tasks', name: 'app_task_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateTaskCommand $command): JsonResponse
    {
        /** @var CreateTaskResult $result */
        $result = $this->handle($command);

        return new JsonResponse($result, Response::HTTP_CREATED);
    }
}
