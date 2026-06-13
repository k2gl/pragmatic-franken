<?php

declare(strict_types=1);

namespace App\Context\Task\Shared;

use InvalidArgumentException;

/**
 * Context-wide value object (ADR-0009): a task title is never blank and fits the
 * column. The rule lives here once and holds on every path — HTTP, CLI, seeder,
 * queue, tests — not only behind #[Assert] at the HTTP edge (ADR-0018).
 */
final readonly class TaskTitle
{
    public const MAX_LENGTH = 255;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('Task title must not be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Task title cannot exceed %d characters.', self::MAX_LENGTH));
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
