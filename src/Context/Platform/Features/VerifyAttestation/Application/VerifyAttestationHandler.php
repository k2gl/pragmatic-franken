<?php

declare(strict_types=1);

namespace App\Context\Platform\Features\VerifyAttestation\Application;

use K2gl\InToto\Statement;
use K2gl\Sigstore\Bundle;
use K2gl\Sigstore\Exception\SigstoreException;
use K2gl\Sigstore\IdentityPolicy;
use K2gl\Sigstore\SigstoreVerifier;
use K2gl\Sigstore\SubjectPolicy;
use K2gl\Sigstore\TrustedRoot;
use K2gl\Slsa\Provenance;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Offline, fail-closed verification of a GitHub Artifact Attestation
 * (Sigstore bundle) for a local artifact — ADR-0014. Checks all four claims:
 * the signature chains to the Sigstore trusted root, the transparency-log
 * inclusion holds, the signer identity is THIS repository's workflow, and the
 * subject digest matches the artifact byte-for-byte. Any failure throws.
 */
#[AsMessageHandler]
final readonly class VerifyAttestationHandler
{
    public function __invoke(VerifyAttestationQuery $query): VerifyAttestationResult
    {
        $bundle = $this->loadBundle($query->bundlePath);
        $identity = IdentityPolicy::githubActions(
            repository: $query->repository,
            workflow: $query->workflow,
            ref: $query->ref,
        );
        $subject = new SubjectPolicy('sha256', $this->sha256Of($query->artifactPath));

        $verifier = new SigstoreVerifier;
        $lastFailure = null;

        // `gh attestation trusted-root` emits one trusted root per line
        // (Sigstore public good + GitHub's PKI) — the bundle must verify
        // under one of them. No trusted-root file → fetch the public-good
        // root via TUF (the only network call, opt-in by omission).
        foreach ($this->trustedRoots($query->trustedRootPath) as $trustedRoot) {
            try {
                $envelope = $verifier->verify(
                    bundle: $bundle,
                    trustedRoot: $trustedRoot,
                    identityPolicy: $identity,
                    subjectPolicy: $subject,
                );

                $statement = Statement::fromEnvelope($envelope);
                $provenance = Provenance::fromStatement($statement);

                return new VerifyAttestationResult(
                    subjectName: $statement->subject[0]->name ?? '(unnamed)',
                    subjectDigest: $statement->subject[0]->digest['sha256'] ?? '',
                    builderId: $provenance->runDetails->builder->id,
                    sourceCommit: $provenance->buildDefinition->resolvedDependencies[0]->digest['gitCommit'] ?? null,
                );
            } catch (SigstoreException $exception) {
                $lastFailure = $exception;
            }
        }

        throw $lastFailure ?? new RuntimeException('No trusted roots available for verification.');
    }

    private function loadBundle(string $path): Bundle
    {
        $raw = @file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException(sprintf('Cannot read attestation bundle "%s".', $path));
        }

        // `gh attestation download` writes JSON Lines: one bundle per line.
        $firstLine = strtok(trim($raw), "\n");
        \assert(\is_string($firstLine));

        return Bundle::fromJson($firstLine);
    }

    /**
     * @return iterable<TrustedRoot>
     */
    private function trustedRoots(?string $path): iterable
    {
        if ($path === null) {
            yield TrustedRoot::fromSigstorePublicGood();

            return;
        }

        $raw = @file_get_contents($path);

        if ($raw === false) {
            throw new RuntimeException(sprintf('Cannot read trusted root "%s".', $path));
        }

        foreach (explode("\n", trim($raw)) as $line) {
            if (trim($line) !== '') {
                yield TrustedRoot::fromJson($line);
            }
        }
    }

    private function sha256Of(string $path): string
    {
        $digest = @hash_file('sha256', $path);

        if ($digest === false) {
            throw new RuntimeException(sprintf('Cannot read artifact "%s".', $path));
        }

        return $digest;
    }
}
