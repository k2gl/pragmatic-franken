<?php

declare(strict_types=1);

namespace App\Context\Platform\Features\VerifyAttestation\Application;

final readonly class VerifyAttestationResult
{
    public function __construct(
        public string $subjectName,
        public string $subjectDigest,
        public string $builderId,
        public ?string $sourceCommit,
    ) {}
}
