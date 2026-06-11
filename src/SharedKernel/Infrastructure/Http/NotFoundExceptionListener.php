<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Http;

use App\SharedKernel\Domain\Env;
use App\SharedKernel\Infrastructure\Persistence\EntityNotFoundException;
use K2gl\Component\AppEnv\Services\AppEnv;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Maps {@see EntityNotFoundException} (repository get() misses, ADR-0013) to a
 * 404 problem+json. The exception message names the entity FQCN — exposed only
 * in dev to avoid leaking internals.
 */
#[AsEventListener]
final readonly class NotFoundExceptionListener
{
    public function __construct(
        private AppEnv $appEnv,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        do {
            if ($throwable instanceof EntityNotFoundException) {
                $body = [
                    'type' => 'about:blank',
                    'title' => 'Not found.',
                    'status' => Response::HTTP_NOT_FOUND,
                ];

                if ($this->appEnv->is(Env::Dev)) {
                    $body['detail'] = $throwable->getMessage();
                }

                $event->setResponse(new JsonResponse(
                    data: $body,
                    status: Response::HTTP_NOT_FOUND,
                    headers: ['Content-Type' => 'application/problem+json'],
                ));

                return;
            }
        } while (($throwable = $throwable->getPrevious()) !== null);
    }
}
