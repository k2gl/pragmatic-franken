<?php

declare(strict_types=1);

namespace App\Tests\Support\Faker;

use Faker\Factory;
use Faker\Generator;

/**
 * Faker access via `$this->faker()`: the generator is built once per test and
 * reused. Foundry factories don't need this trait — they have their own
 * `self::faker()`. Add domain providers here when the project grows them —
 * real projects extend this into a typed Generator the same way.
 */
trait FakerTrait
{
    private ?Generator $faker = null;

    protected function faker(): Generator
    {
        return $this->faker ??= Factory::create();
    }
}
