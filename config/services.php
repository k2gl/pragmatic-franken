<?php

declare(strict_types=1);

use K2gl\Component\AppEnv\Services\AppEnv;
use K2gl\Component\Validator\Constraint\EntityExist\AssertEntityNotExistValidator;
use Predis\Client as PredisClient;
use Predis\ClientInterface as PredisClientInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->load('App\\', '../src/')
            ->exclude([
                '../src/Kernel.php',
                '../src/Context/*/Entity/',
                '../src/Context/*/Enum/',
                '../src/Context/*/Features/*/Domain/',
            ]);

    $container->services()
        ->instanceof('App\\**\\*Controller')
            ->tag('controller.service_arguments');

    $container->services()
        ->instanceof('App\\**\\*Handler')
            ->tag('messenger.message_handler');

    // Redis client (Predis) bound to REDIS_URL.
    $container->services()
        ->set(PredisClient::class)
            ->args(['%env(REDIS_URL)%'])
        ->alias(PredisClientInterface::class, PredisClient::class);

    // k2gl/app-env — type-safe environment checks (App\SharedKernel\Domain\Env).
    $container->services()
        ->set(AppEnv::class)
            ->args(['%kernel.environment%']);

    // k2gl/entity-exist validator — K2gl\ namespace, outside the App\ autoconfig scope.
    $container->services()
        ->set(AssertEntityNotExistValidator::class)
            ->args([service('doctrine.orm.entity_manager')])
            ->tag('validator.constraint_validator');
};
