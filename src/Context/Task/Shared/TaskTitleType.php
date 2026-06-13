<?php

declare(strict_types=1);

namespace App\Context\Task\Shared;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Maps the TaskTitle value object onto a plain VARCHAR column (no schema change —
 * the same column the entity used before). Registered as `task_title` in
 * config/packages/doctrine.yaml; see ADR-0018.
 */
final class TaskTitleType extends StringType
{
    public const NAME = 'task_title';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?TaskTitle
    {
        if ($value === null) {
            return null;
        }

        \assert(is_string($value));

        return new TaskTitle($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof TaskTitle) {
            return $value->value;
        }

        \assert($value === null || is_string($value));

        return $value;
    }
}
