<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** HTTP input contract — validated by #[MapRequestPayload] before the command is built. */
final readonly class CreateTaskRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
    ) {}
}
