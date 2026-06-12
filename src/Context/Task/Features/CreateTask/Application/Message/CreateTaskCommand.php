<?php

declare(strict_types=1);

namespace App\Context\Task\Features\CreateTask\Application\Message;

use Symfony\Component\Validator\Constraints as Assert;

/** Mapped straight from the request payload (ADR-0003: no extra DTO layer). */
final readonly class CreateTaskCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
    ) {}
}
