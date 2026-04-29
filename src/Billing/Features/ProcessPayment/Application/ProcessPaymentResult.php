<?php

declare(strict_types=1);

namespace App\Billing\Features\ProcessPayment\Application;

final readonly class ProcessPaymentResult
{
    public function __construct(
        public string $paymentId,
        public int $amountCents,
        public string $currency,
        public \DateTimeImmutable $processedAt,
    ) {
    }
}
