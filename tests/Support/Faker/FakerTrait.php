<?php

declare(strict_types=1);

namespace App\Tests\Support\Faker;

use Faker\Factory;
use Faker\Generator;

/**
 * Faker access via `$this->faker()`: the generator is built once per test and
 * reused. Foundry factories don't need this trait — they have their own
 * `self::faker()`. Add domain providers here when the project grows them
 * (see the typed-Generator pattern in the CRM for the full version).
 */
trait FakerTrait
{
    private ?Generator $faker = null;

    protected function faker(): Generator
    {
        return $this->faker ??= Factory::create();
    }
}
