<?php

declare(strict_types=1);

namespace App\Tests\Context\Billing\Features\ProcessPayment;

use App\Context\Billing\Features\ProcessPayment\Application\ProcessPaymentCommand;
use App\Context\Billing\Features\ProcessPayment\Application\ProcessPaymentHandler;
use App\Context\Billing\Features\ProcessPayment\Application\ProcessPaymentResult;
use App\Context\Billing\Features\ProcessPayment\Domain\PaymentProcessed;
use App\Tests\Support\TestCase\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[Group('unit')]
final class ProcessPaymentHandlerTest extends UnitTestCase
{
    public function test_returns_result_and_dispatches_domain_event(): void
    {
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentProcessed::class))
            ->willReturnCallback(function (object $msg) use (&$dispatched): Envelope {
                $dispatched[] = $msg;

                return new Envelope($msg);
            });

        $result = (new ProcessPaymentHandler($bus))(
            new ProcessPaymentCommand(amountCents: 4999, currency: 'EUR')
        );

        self::assertInstanceOf(ProcessPaymentResult::class, $result);
        self::assertSame(4999, $result->amountCents);
        self::assertSame('EUR', $result->currency);
        self::assertNotEmpty($result->paymentId);

        /** @var PaymentProcessed $event */
        $event = $dispatched[0];
        self::assertSame($result->paymentId, $event->paymentId);
        self::assertSame(4999, $event->amountCents);
    }

    public function test_each_call_produces_unique_payment_id(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(fn (object $m) => new Envelope($m));

        $handler = new ProcessPaymentHandler($bus);
        $cmd = new ProcessPaymentCommand(amountCents: 100, currency: 'USD');

        $ids = [
            $handler($cmd)->paymentId,
            $handler($cmd)->paymentId,
        ];

        self::assertNotSame($ids[0], $ids[1]);
    }
}
