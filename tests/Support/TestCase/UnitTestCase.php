<?php

declare(strict_types=1);

namespace App\Tests\Support\TestCase;

use PHPUnit\Framework\TestCase;

/**
 * Base for pure unit tests — no kernel, no database.
 * Tag your test class with #[Group('unit')].
 */
abstract class UnitTestCase extends TestCase {}
