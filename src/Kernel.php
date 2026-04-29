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
        foreach (glob($this->getProjectDir().'/src/*/Features/*/EntryPoint/Http', GLOB_ONLYDIR) ?: [] as $dir) {
            $routes->import($dir, 'attribute');
        }
        // Optional global controllers under src/Controller/ if a project adds one.
        if (is_dir($this->getProjectDir().'/src/Controller')) {
            $routes->import($this->getProjectDir().'/src/Controller', 'attribute');
        }
        // Per-environment overrides (e.g. test routes).
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}.yaml');
    }
}
