<?php

declare(strict_types=1);

namespace App\Context\Billing\Features\ProcessPayment\Application;

use App\Context\Billing\Features\ProcessPayment\Domain\PaymentProcessed;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ProcessPaymentHandler
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(ProcessPaymentCommand $command): ProcessPaymentResult
    {
        $paymentId = (string) Uuid::v7();
        $processedAt = new \DateTimeImmutable();

        // Dispatch domain event so other bounded contexts can react asynchronously.
        $this->eventBus->dispatch(new PaymentProcessed(
            paymentId: $paymentId,
            amountCents: $command->amountCents,
            currency: $command->currency,
            processedAt: $processedAt,
        ));

        return new ProcessPaymentResult(
            paymentId: $paymentId,
            amountCents: $command->amountCents,
            currency: $command->currency,
            processedAt: $processedAt,
        );
    }
}
