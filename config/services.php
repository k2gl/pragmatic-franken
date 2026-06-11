<?php

declare(strict_types=1);

use Predis\Client as PredisClient;
use Predis\ClientInterface as PredisClientInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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
};
