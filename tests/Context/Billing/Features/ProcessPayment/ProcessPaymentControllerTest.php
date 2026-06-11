<?php

declare(strict_types=1);

namespace App\Tests\Context\Billing\Features\ProcessPayment;

use App\Tests\Support\TestCase\ApiTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('e2e')]
final class ProcessPaymentControllerTest extends ApiTestCase
{
    public function test_post_returns_created_with_payment_id(): void
    {
        $body = $this->postJson('/billing/payment', [
            'amount_cents' => 1999,
            'currency' => 'USD',
        ]);

        self::assertSame(201, $this->client->getResponse()->getStatusCode());
        self::assertArrayHasKey('payment_id', $body);
        self::assertSame(1999, $body['amount_cents']);
        self::assertSame('USD', $body['currency']);
        self::assertArrayHasKey('processed_at', $body);
    }
}
