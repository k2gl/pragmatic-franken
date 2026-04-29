<?php

declare(strict_types=1);

namespace App\Tests\Billing\Features\ProcessPayment;

use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('e2e')]
final class ProcessPaymentControllerTest extends ApiTestCase
{
    public function test_post_returns_created_with_payment_id(): void
    {
        $this->postJson('/billing/payment', [
            'amount_cents' => 1999,
            'currency' => 'USD',
        ]);

        self::assertSame(201, $this->client->getResponse()->getStatusCode());

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('payment_id', $body);
        self::assertArrayHasKey('amount_cents', $body);
        self::assertSame(1999, $body['amount_cents']);
        self::assertSame('USD', $body['currency']);
        self::assertArrayHasKey('processed_at', $body);
    }
}
