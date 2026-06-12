<?php

declare(strict_types=1);

namespace App\Tests\Context\Health\Features\Healthz;

use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

#[Group('e2e')]
final class HealthzControllerTest extends ApiTestCase
{
    public function test_healthz_liveness_returns_ok(): void
    {
        $payload = $this->getJson('/healthz');

        fact($this->responseStatusCode())->is(200);
        fact($payload)->is(['ok' => true]);
    }

    public function test_ready_reports_dependency_status(): void
    {
        $payload = $this->getJson('/ready');

        // 200 if dependencies are up, 503 otherwise — both confirm the endpoint is wired.
        fact([200, 503])->contains($this->responseStatusCode());
        fact($payload)->arrayHasKey('ok')->arrayHasKey('db')->arrayHasKey('redis');
    }
}
