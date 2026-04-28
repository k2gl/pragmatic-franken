<?php

declare(strict_types=1);

namespace App\Tests\Health\Features\Healthz;

use App\Health\Features\Healthz\Application\CheckHealthHandler;
use App\Health\Features\Healthz\Application\CheckHealthQuery;
use App\Health\Features\Healthz\Application\HealthStatus;
use App\Health\Features\Healthz\Infrastructure\DbPing;
use App\Health\Features\Healthz\Infrastructure\RedisPing;
use App\Tests\Support\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
final class CheckHealthHandlerTest extends UnitTestCase
{
    public function test_returns_ok_when_both_pings_succeed(): void
    {
        $db = $this->createMock(DbPing::class);
        $db->method('isAlive')->willReturn(true);

        $redis = $this->createMock(RedisPing::class);
        $redis->method('isAlive')->willReturn(true);

        $handler = new CheckHealthHandler($db, $redis);
        $status = $handler(new CheckHealthQuery());

        self::assertInstanceOf(HealthStatus::class, $status);
        self::assertTrue($status->ok());
    }

    public function test_returns_not_ok_when_db_is_down(): void
    {
        $db = $this->createMock(DbPing::class);
        $db->method('isAlive')->willReturn(false);

        $redis = $this->createMock(RedisPing::class);
        $redis->method('isAlive')->willReturn(true);

        $handler = new CheckHealthHandler($db, $redis);
        $status = $handler(new CheckHealthQuery());

        self::assertFalse($status->ok());
        self::assertFalse($status->db);
        self::assertTrue($status->redis);
    }
}
