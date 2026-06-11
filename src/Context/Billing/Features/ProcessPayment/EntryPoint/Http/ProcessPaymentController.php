<?php

declare(strict_types=1);

namespace App\Context\Billing\Features\ProcessPayment\EntryPoint\Http;

use App\Context\Billing\Features\ProcessPayment\Application\ProcessPaymentCommand;
use App\Context\Billing\Features\ProcessPayment\Application\ProcessPaymentResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ProcessPaymentController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/billing/payment', name: 'app_billing_process_payment', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $body = $request->toArray();

        $amountCents = \is_int($body['amount_cents'] ?? null) ? $body['amount_cents'] : 0;
        $currency = \is_string($body['currency'] ?? null) ? $body['currency'] : 'USD';

        /** @var ProcessPaymentResult $result */
        $result = $this->handle(new ProcessPaymentCommand(
            amountCents: $amountCents,
            currency: $currency,
        ));

        return new JsonResponse([
            'payment_id' => $result->paymentId,
            'amount_cents' => $result->amountCents,
            'currency' => $result->currency,
            'processed_at' => $result->processedAt->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_CREATED);
    }
}
