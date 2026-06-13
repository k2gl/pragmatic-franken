<?php

declare(strict_types=1);

namespace App\Context\Notification\Features\LiveUpdates\Application\Message;

use InvalidArgumentException;

/**
 * Aggregate-less write (ADR-0018): with no entity to guard the invariant, the
 * command guards itself in the constructor — an imperative check, never #[Assert]
 * (which stays at the HTTP edge on PublishLiveUpdateRequest).
 */
final readonly class PublishLiveUpdateCommand
{
    /**
     * @param mixed[] $data
     */
    public function __construct(
        public string $topic,
        public array $data,
        public bool $private = false,
    ) {
        if (trim($topic) === '') {
            throw new InvalidArgumentException('Live update topic must not be blank.');
        }
    }
}
