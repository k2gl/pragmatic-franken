<?php

declare(strict_types=1);

namespace App\Tests\Context\Notification\Features\LiveUpdates;

use App\Context\Notification\Features\LiveUpdates\Application\Dto\LiveUpdateResult;
use App\Context\Notification\Features\LiveUpdates\Application\Message\PublishLiveUpdateCommand;
use App\Context\Notification\Features\LiveUpdates\Application\PublishLiveUpdateHandler;
use App\Tests\Support\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('unit')]
final class PublishLiveUpdateHandlerTest extends UnitTestCase
{
    public function test_publishes_update_and_returns_message_id(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::once())
            ->method('publish')
            ->with(self::callback(fn (Update $u) => $u->getTopics() === ['/board/1']))
            ->willReturn('msg-abc-123');

        $handler = new PublishLiveUpdateHandler($hub);
        $result = $handler(new PublishLiveUpdateCommand(
            topic: '/board/1',
            data: ['event' => 'task_created'],
        ));

        fact($result)->instanceOf(LiveUpdateResult::class);
        fact($result->messageId)->is('msg-abc-123');
    }

    public function test_private_flag_is_forwarded_to_update(): void
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::once())
            ->method('publish')
            ->with(self::callback(fn (Update $u) => $u->isPrivate() === true))
            ->willReturn('msg-prv-456');

        $handler = new PublishLiveUpdateHandler($hub);
        $handler(new PublishLiveUpdateCommand(
            topic: '/user/42',
            data: [],
            private: true,
        ));
    }
}
