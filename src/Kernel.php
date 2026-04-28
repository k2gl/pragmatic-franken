<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Attribute-based routes from every slice's EntryPoint/Http/ controllers.
        $routes->import('../src/*/Features/*/EntryPoint/Http', 'attribute');
        // Optional global controllers under src/Controller/ if a project adds one.
        $routes->import('../src/Controller/', 'attribute');
        // Per-environment overrides (e.g. test routes).
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}.yaml');
    }
}
