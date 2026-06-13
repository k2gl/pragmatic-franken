<?php

declare(strict_types=1);

namespace App\Context\Notification\Features\LiveUpdates\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** HTTP input contract — validated by #[MapRequestPayload] before the command is built. */
final readonly class PublishLiveUpdateRequest
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        #[Assert\NotBlank]
        public string $topic,
        public array $data = [],
        public bool $private = false,
    ) {}
}
