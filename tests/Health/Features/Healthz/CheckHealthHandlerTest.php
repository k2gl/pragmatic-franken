<?php

declare(strict_types=1);

namespace App\Tests\Health\Features\Healthz;

use App\Health\Features\Healthz\Application\CheckHealthHandler;
use App\Health\Features\Healthz\Application\CheckHealthQuery;
use App\Health\Features\Healthz\Application\HealthStatus;
use App\Health\Features\Healthz\Infrastructure\DbPingInterface;
use App\Health\Features\Healthz\Infrastructure\RedisPingInterface;
use App\Tests\Support\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
final class CheckHealthHandlerTest extends UnitTestCase
{
    public function test_returns_ok_when_both_pings_succeed(): void
    {
        $db = $this->createMock(DbPingInterface::class);
        $db->method('isAlive')->willReturn(true);

        $redis = $this->createMock(RedisPingInterface::class);
        $redis->method('isAlive')->willReturn(true);

        $handler = new CheckHealthHandler($db, $redis);
        $status = $handler(new CheckHealthQuery());

        self::assertInstanceOf(HealthStatus::class, $status);
        self::assertTrue($status->ok());
    }

    public function test_returns_not_ok_when_db_is_down(): void
    {
        $db = $this->createMock(DbPingInterface::class);
        $db->method('isAlive')->willReturn(false);

        $redis = $this->createMock(RedisPingInterface::class);
        $redis->method('isAlive')->willReturn(true);

        $handler = new CheckHealthHandler($db, $redis);
        $status = $handler(new CheckHealthQuery());

        self::assertFalse($status->ok());
        self::assertFalse($status->db);
        self::assertTrue($status->redis);
    }
}
