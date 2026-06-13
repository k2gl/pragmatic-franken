<?php

declare(strict_types=1);

namespace App\Context\Notification\Features\LiveUpdates\EntryPoint\Http;

use App\Context\Notification\Features\LiveUpdates\Application\Dto\LiveUpdateResult;
use App\Context\Notification\Features\LiveUpdates\Application\Dto\PublishLiveUpdateRequest;
use App\Context\Notification\Features\LiveUpdates\Application\Message\PublishLiveUpdateCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
    public function __invoke(#[MapRequestPayload] PublishLiveUpdateRequest $request): JsonResponse
    {
        /** @var LiveUpdateResult $result */
        $result = $this->handle(new PublishLiveUpdateCommand(
            topic: $request->topic,
            data: $request->data,
            private: $request->private,
        ));

        return new JsonResponse(['data' => $result], Response::HTTP_CREATED);
    }
}
