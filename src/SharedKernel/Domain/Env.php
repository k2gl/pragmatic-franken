<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

use K2gl\Enum\ExtendedBackedEnum;
use K2gl\Enum\ExtendedBackedEnumInterface;

/** Application environment (values match APP_ENV / %kernel.environment%). */
enum Env: string implements ExtendedBackedEnumInterface
{
    use ExtendedBackedEnum;

    case Dev = 'dev';
    case Test = 'test';
    case Prod = 'prod';
}
