<?php

declare(strict_types=1);

namespace App\Context\Billing\Features\ProcessPayment\Domain;

final readonly class PaymentProcessed
{
    public function __construct(
        public string $paymentId,
        public int $amountCents,
        public string $currency,
        public \DateTimeImmutable $processedAt,
    ) {
    }
}
