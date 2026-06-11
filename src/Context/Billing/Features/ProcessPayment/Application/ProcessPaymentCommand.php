<?php

declare(strict_types=1);

namespace App\Context\Billing\Features\ProcessPayment\Application;

final readonly class ProcessPaymentCommand
{
    public function __construct(
        public int $amountCents,
        public string $currency,
    ) {
    }
}
