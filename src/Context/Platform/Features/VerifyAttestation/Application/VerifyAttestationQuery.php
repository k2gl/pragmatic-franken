<?php

declare(strict_types=1);

namespace App\Context\Platform\Features\VerifyAttestation\Application;

final readonly class VerifyAttestationQuery
{
    public function __construct(
        public string $artifactPath,
        public string $bundlePath,
        public string $repository,
        public string $workflow = 'release.yml',
        public ?string $ref = null,
        public ?string $trustedRootPath = null,
    ) {}
}
