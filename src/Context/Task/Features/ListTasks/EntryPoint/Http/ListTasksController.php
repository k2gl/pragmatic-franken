<?php

declare(strict_types=1);

namespace App\Context\Task\Features\ListTasks\EntryPoint\Http;

use App\Context\Task\Features\ListTasks\Application\ListTasksQuery;
use App\Context\Task\Features\ListTasks\Application\ListTasksResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ListTasksController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/tasks', name: 'app_task_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var ListTasksResult $result */
        $result = $this->handle(new ListTasksQuery);

        return new JsonResponse($result);
    }
}
