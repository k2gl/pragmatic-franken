<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\Application\Dto;

use App\Context\Task\Shared\TaskTitle;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * HTTP input contract — validated by #[MapRequestPayload] before the command is
 * built (ADR-0018). The edge mirrors the TaskTitle invariant for a 422 with
 * violations; TaskTitle itself stays the single source of the rule and limit.
 */
final readonly class CreateTaskRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: TaskTitle::MAX_LENGTH)]
        public string $title,
    ) {}
}
