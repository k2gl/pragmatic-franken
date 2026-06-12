<?php

declare(strict_types=1);

namespace App\Tests\Support\TestCase\Mixins;

use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

/**
 * Test-container service access: type-safe `container(Foo::class)` instead of
 * `getContainer()->get(...) + assert instanceof`, plus auto-injection of
 * services into public typed properties via `setServicesFromContainer()`.
 */
trait ServicesContainerTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    protected function container(string $className): object
    {
        $service = static::getContainer()->get($className);
        \assert($service instanceof $className);

        return $service;
    }

    protected function setServicesFromContainer(): void
    {
        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = (string) $property->getType();

            if (! static::getContainer()->has($type)) {
                throw new RuntimeException(sprintf('Service "%s" is not registered in the test container.', $type));
            }

            $this->{$property->getName()} = static::getContainer()->get($type);
        }
    }
}
