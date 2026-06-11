<?php

declare(strict_types=1);

namespace App\Context\Platform\Features\VerifyAttestation\EntryPoint\Cli;

use App\Context\Platform\Features\VerifyAttestation\Application\VerifyAttestationQuery;
use App\Context\Platform\Features\VerifyAttestation\Application\VerifyAttestationResult;
use K2gl\Sigstore\Exception\SigstoreException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Deploy gate (ADR-0018): verify a GitHub Artifact Attestation for a local
 * artifact offline before trusting it. Exit code 1 on ANY verification
 * failure — fail closed.
 *
 *   bin/console app:verify-attestation dist/app.tar.gz dist/app.tar.gz.sigstore.jsonl \
 *       --repository=k2gl/pragmatic-franken --workflow=release.yml \
 *       --trusted-root=trusted_root.jsonl
 */
#[AsCommand(name: 'app:verify-attestation', description: 'Verify a GitHub Artifact Attestation (Sigstore bundle) for a local artifact — offline, fail-closed')]
final class VerifyAttestationCliCommand extends Command
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('artifact', InputArgument::REQUIRED, 'Path to the artifact file')
            ->addArgument('bundle', InputArgument::REQUIRED, 'Path to the .sigstore.jsonl attestation (gh attestation download)')
            ->addOption('repository', null, InputOption::VALUE_REQUIRED, 'owner/repo that must have signed', 'k2gl/pragmatic-franken')
            ->addOption('workflow', null, InputOption::VALUE_REQUIRED, 'Signing workflow file name', 'release.yml')
            ->addOption('ref', null, InputOption::VALUE_REQUIRED, 'Git ref the signer must have built (e.g. refs/heads/main)')
            ->addOption('trusted-root', null, InputOption::VALUE_REQUIRED, 'Path to trusted root JSONL (gh attestation trusted-root) — offline mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $artifact = $input->getArgument('artifact');
        $bundle = $input->getArgument('bundle');
        $repository = $input->getOption('repository');
        $workflow = $input->getOption('workflow');
        $ref = $input->getOption('ref');
        $trustedRoot = $input->getOption('trusted-root');
        \assert(\is_string($artifact) && \is_string($bundle) && \is_string($repository) && \is_string($workflow));
        \assert($ref === null || \is_string($ref));
        \assert($trustedRoot === null || \is_string($trustedRoot));

        try {
            /** @var VerifyAttestationResult $result */
            $result = $this->handle(new VerifyAttestationQuery(
                artifactPath: $artifact,
                bundlePath: $bundle,
                repository: $repository,
                workflow: $workflow,
                ref: $ref,
                trustedRootPath: $trustedRoot,
            ));
        } catch (SigstoreException $exception) {
            $io->error(sprintf('VERIFICATION FAILED: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        $io->success('VERIFIED');
        $io->listing([
            sprintf('subject:  %s (sha256:%s)', $result->subjectName, $result->subjectDigest),
            sprintf('builder:  %s', $result->builderId),
            sprintf('commit:   %s', $result->sourceCommit ?? 'n/a'),
        ]);

        return Command::SUCCESS;
    }
}
