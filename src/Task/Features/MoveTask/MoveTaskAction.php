<?php

namespace App\Task\Features\MoveTask;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Tasks")]
final class MoveTaskAction extends AbstractController
{
    #[Route('/api/tasks/{id}/move', name: 'move_task', methods: ['POST'])]
    public function __invoke(
        int $id,
        #[MapRequestPayload] MoveTaskMessage $message,
        MessageBusInterface $bus
    ): MoveTaskResult {
        $envelope = $bus->dispatch($message->withTaskId($id));
        return $envelope->last(HandledStamp::class)->getResult();
    }
}
