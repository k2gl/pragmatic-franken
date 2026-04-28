<?php

declare(strict_types=1);

namespace App\Tests\Support\TestCase;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Base for integration tests — boots Symfony kernel, uses real DB.
 * DAMA wraps each test in a transaction rolled back on teardown (registered in phpunit.xml).
 * Tag your test class with #[Group('integration')].
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
}
