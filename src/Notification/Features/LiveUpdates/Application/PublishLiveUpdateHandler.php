<?php

declare(strict_types=1);

namespace App\Notification\Features\LiveUpdates\Application;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishLiveUpdateHandler
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function __invoke(PublishLiveUpdateCommand $command): LiveUpdateResult
    {
        $update = new Update(
            topics: $command->topic,
            data: json_encode($command->data, \JSON_THROW_ON_ERROR),
            private: $command->private,
        );

        $messageId = $this->hub->publish($update);

        return new LiveUpdateResult($messageId);
    }
}
