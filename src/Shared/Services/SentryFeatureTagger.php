<?php

namespace App\Shared\Services;

use Sentry\State\Scope;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

function sentry_before_send(\Throwable $exception, array $hint): ?\Throwable
{
    return $exception;
}

final class SentryFeatureTagger
{
    public function __construct(
        private bool $enabled = true
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $request = $event->getRequest();
        $controller = $request->attributes->get('_controller');

        if (!$controller || !is_string($controller)) {
            return;
        }

        $this->tagFromController($controller, $request);
    }

    private function tagFromController(string $controller, $request): void
    {
        if (preg_match('/App\\\\(\w+)\\\\UseCase\\\\(\w+)/', $controller, $matches)) {
            $scope = new Scope();
            $scope->setTag('module', $matches[1]);
            $scope->setTag('feature', $matches[2]);

            $requestUri = $request->getUri();
            $scope->setTag('route', $request->attributes->get('_route', 'unknown'));

            $scope->setExtra('request_uri', $requestUri);
            $scope->setExtra('http_method', $request->getMethod());

            if (function_exists('Sentry\configureScope')) {
                \Sentry\configureScope(function (Scope $scope) use ($matches, $requestUri, $request): void {
                    $scope->setTag('module', $matches[1]);
                    $scope->setTag('feature', $matches[2]);
                    $scope->setTag('route', $request->attributes->get('_route', 'unknown'));
                    $scope->setExtra('request_uri', $requestUri);
                    $scope->setExtra('http_method', $request->getMethod());
                });
            }
        }
    }
}
