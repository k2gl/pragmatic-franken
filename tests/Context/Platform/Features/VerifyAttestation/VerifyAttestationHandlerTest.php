<?php

declare(strict_types=1);

namespace App\Tests\Context\Platform\Features\VerifyAttestation;

use App\Context\Platform\Features\VerifyAttestation\Application\VerifyAttestationHandler;
use App\Context\Platform\Features\VerifyAttestation\Application\VerifyAttestationQuery;
use App\Tests\Support\TestCase\UnitTestCase;
use K2gl\Sigstore\Exception\SigstoreException;
use PHPUnit\Framework\Attributes\Group;

use function K2gl\PHPUnitFluentAssertions\fact;

/**
 * Fully offline: real signed artifact + attestation + pinned trusted roots
 * committed as fixtures. Proves the gate verifies AND that it fails closed.
 */
#[Group('unit')]
final class VerifyAttestationHandlerTest extends UnitTestCase
{
    private const FIXTURES = __DIR__.'/fixtures';

    public function test_valid_attestation_verifies(): void
    {
        $result = (new VerifyAttestationHandler())(new VerifyAttestationQuery(
            artifactPath: self::FIXTURES.'/artifact.tar.gz',
            bundlePath: self::FIXTURES.'/artifact.tar.gz.sigstore.jsonl',
            repository: 'k2gl/sigstore-verify',
            workflow: 'attest.yml',
            trustedRootPath: self::FIXTURES.'/trusted_root.jsonl',
        ));

        fact(str_contains($result->builderId, 'github.com'))->true();
        fact($result->subjectDigest)->is(hash_file('sha256', self::FIXTURES.'/artifact.tar.gz'));
    }

    public function test_tampered_artifact_fails_closed(): void
    {
        $tampered = tempnam(sys_get_temp_dir(), 'pf-tampered-');
        \assert(\is_string($tampered));
        file_put_contents($tampered, file_get_contents(self::FIXTURES.'/artifact.tar.gz').'x');

        try {
            $this->expectException(SigstoreException::class);

            (new VerifyAttestationHandler())(new VerifyAttestationQuery(
                artifactPath: $tampered,
                bundlePath: self::FIXTURES.'/artifact.tar.gz.sigstore.jsonl',
                repository: 'k2gl/sigstore-verify',
                workflow: 'attest.yml',
                trustedRootPath: self::FIXTURES.'/trusted_root.jsonl',
            ));
        } finally {
            @unlink($tampered);
        }
    }

    public function test_wrong_signer_identity_fails_closed(): void
    {
        $this->expectException(SigstoreException::class);

        (new VerifyAttestationHandler())(new VerifyAttestationQuery(
            artifactPath: self::FIXTURES.'/artifact.tar.gz',
            bundlePath: self::FIXTURES.'/artifact.tar.gz.sigstore.jsonl',
            repository: 'evil/sigstore-verify',
            workflow: 'attest.yml',
            trustedRootPath: self::FIXTURES.'/trusted_root.jsonl',
        ));
    }
}
