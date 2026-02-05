<?php

declare(strict_types=1);

namespace App\Task\Features\CreateTask;

use App\User\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Tasks')]
#[Route('/api/tasks', methods: ['POST'])]
final class CreateTaskAction extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateTaskMessage $message,
        MessageBusInterface $bus
    ): TaskCreatedResponse {
        $user = $this->getUser();
        assert($user instanceof User);
        $message->setUser($user);
        $envelope = $bus->dispatch($message);
        return $envelope->last(HandledStamp::class)->getResult();
    }
}
