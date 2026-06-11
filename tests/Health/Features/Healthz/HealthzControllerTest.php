<?php

declare(strict_types=1);

namespace App\Tests\Health\Features\Healthz;

use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('e2e')]
final class HealthzControllerTest extends ApiTestCase
{
    public function test_healthz_liveness_returns_ok(): void
    {
        $this->client->request('GET', '/healthz');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertSame(['ok' => true], $payload);
    }

    public function test_ready_reports_dependency_status(): void
    {
        $this->client->request('GET', '/ready');

        // 200 if dependencies are up, 503 otherwise — both confirm the endpoint is wired.
        self::assertContains($this->client->getResponse()->getStatusCode(), [200, 503]);

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('ok', $payload);
        self::assertArrayHasKey('db', $payload);
        self::assertArrayHasKey('redis', $payload);
    }
}
