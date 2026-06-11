<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Turns `#[MapRequestPayload]` validation failures into one RFC 9457
 * problem+json envelope: `422 {type, title, status, violations:[{propertyPath,
 * message, code}]}`. `code` is the stable violation code (the constraint's
 * UUID, e.g. `UniqueEntity::NOT_UNIQUE_ERROR`) — assert on it instead of on
 * message text. The argument resolver throws HttpException(422) with
 * ValidationFailedException in `previous`, so we walk the chain.
 */
#[AsEventListener]
final class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        do {
            if ($throwable instanceof ValidationFailedException) {
                $event->setResponse($this->toResponse($throwable));

                return;
            }
        } while (($throwable = $throwable->getPrevious()) !== null);
    }

    private function toResponse(ValidationFailedException $exception): JsonResponse
    {
        $violations = [];

        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
                'code' => (string) $violation->getCode(),
            ];
        }

        return new JsonResponse(
            data: [
                'type' => 'about:blank',
                'title' => 'Validation failed.',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'violations' => $violations,
            ],
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }
}
