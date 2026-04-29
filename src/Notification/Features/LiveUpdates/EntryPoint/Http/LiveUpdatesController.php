<?php

declare(strict_types=1);

namespace App\Notification\Features\LiveUpdates\EntryPoint\Http;

use App\Notification\Features\LiveUpdates\Application\LiveUpdateResult;
use App\Notification\Features\LiveUpdates\Application\PublishLiveUpdateCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class LiveUpdatesController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/notification/live-update', name: 'app_notification_live_update', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $body = $request->toArray();

        $topic = \is_string($body['topic'] ?? null) ? $body['topic'] : '';
        $data = \is_array($body['data'] ?? null) ? $body['data'] : [];
        $private = \is_bool($body['private'] ?? null) ? $body['private'] : false;

        /** @var LiveUpdateResult $result */
        $result = $this->handle(new PublishLiveUpdateCommand(
            topic: $topic,
            data: $data,
            private: $private,
        ));

        return new JsonResponse(['messageId' => $result->messageId], Response::HTTP_CREATED);
    }
}
