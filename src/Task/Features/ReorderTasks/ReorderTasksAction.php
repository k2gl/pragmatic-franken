<?php

namespace App\Task\Features\ReorderTasks;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Tasks")]
final class ReorderTasksAction extends AbstractController
{
    #[Route('/api/tasks/reorder', name: 'reorder_tasks', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] ReorderTasksMessage $message,
        MessageBusInterface $bus
    ): ReorderTasksResult {
        $envelope = $bus->dispatch($message);
        return $envelope->last(HandledStamp::class)->getResult();
    }
}
