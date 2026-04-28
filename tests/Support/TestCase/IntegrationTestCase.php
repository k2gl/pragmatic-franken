<?php

declare(strict_types=1);

namespace App\Tests\Support\TestCase;

use Dama\DoctrineTestBundle\PHPUnit\PHPUnitExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Base for integration tests — boots Symfony kernel, uses real DB.
 * Each test runs in a transaction rolled back on teardown (DAMA).
 * Tag your test class with #[Group('integration')].
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    protected static function getAnnotations(): array
    {
        return [PHPUnitExtension::class];
    }
}
