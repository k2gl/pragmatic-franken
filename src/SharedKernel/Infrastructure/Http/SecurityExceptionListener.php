<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Http;

use App\SharedKernel\Domain\Env;
use K2gl\Component\AppEnv\Services\AppEnv;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Gives an authenticated user without the required grants (`#[IsGranted]`) a
 * problem+json `403` — without this listener the firewall would render a
 * non-JSON response. Unauthenticated requests are left alone: there
 * AccessDeniedException must become a 401 via the firewall entry point, not
 * a 403. Priority is above the firewall's ExceptionListener (1), and we stop
 * propagation so this JSON is final. Inert until the fork adds auth
 * (no user — no match); see docs/recipes/jwt-auth.md.
 */
#[AsEventListener(priority: 16)]
final readonly class SecurityExceptionListener
{
    public function __construct(
        private Security $security,
        private AppEnv $appEnv,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        if ($this->security->getUser() === null) {
            return;
        }

        $throwable = $event->getThrowable();

        do {
            if ($throwable instanceof AccessDeniedException) {
                $body = [
                    'type' => 'about:blank',
                    'title' => 'Access denied.',
                    'status' => Response::HTTP_FORBIDDEN,
                ];

                // Help debugging in dev: expose the voter's actual reason.
                if ($this->appEnv->is(Env::Dev)) {
                    $body['detail'] = $throwable->getMessage();
                }

                $event->setResponse(new JsonResponse(
                    data: $body,
                    status: Response::HTTP_FORBIDDEN,
                    headers: ['Content-Type' => 'application/problem+json'],
                ));
                $event->stopPropagation();

                return;
            }
        } while (($throwable = $throwable->getPrevious()) !== null);
    }
}
