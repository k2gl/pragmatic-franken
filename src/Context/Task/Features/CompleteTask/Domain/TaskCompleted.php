<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CompleteTask\Domain;

/**
 * Domain event (ADR-0011): routed async via Messenger; other bounded contexts
 * subscribe (Notification publishes a Mercure live update).
 */
final readonly class TaskCompleted
{
    public function __construct(
        public string $taskId,
        public string $title,
        public \DateTimeImmutable $completedAt,
    ) {
    }
}
