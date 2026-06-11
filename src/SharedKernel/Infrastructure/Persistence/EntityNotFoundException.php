<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Persistence;

use RuntimeException;

/**
 * Thrown by {@see DoctrineRepository::get()} when no entity exists for the
 * given id. The message is already formatted by the repository
 * (`<FQCN> with id "<id>" is not found`).
 */
final class EntityNotFoundException extends RuntimeException
{
}
