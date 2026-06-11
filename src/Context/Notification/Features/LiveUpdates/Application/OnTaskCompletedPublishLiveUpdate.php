<?php

declare(strict_types=1);

namespace App\Context\Notification\Features\LiveUpdates\Application;

use App\Context\Task\Features\CompleteTask\Domain\TaskCompleted;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Cross-context subscription (ADR-0011): the Task context fires TaskCompleted,
 * Notification reacts by pushing a Mercure live update on the `/tasks` topic.
 * Consumed async — run `bin/console messenger:consume async` to see it flow.
 */
#[AsMessageHandler]
final readonly class OnTaskCompletedPublishLiveUpdate
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(TaskCompleted $event): void
    {
        $this->messageBus->dispatch(new PublishLiveUpdateCommand(
            topic: '/tasks',
            data: [
                'type' => 'task.completed',
                'taskId' => $event->taskId,
                'title' => $event->title,
                'completedAt' => $event->completedAt->format(\DateTimeInterface::ATOM),
            ],
        ));
    }
}
