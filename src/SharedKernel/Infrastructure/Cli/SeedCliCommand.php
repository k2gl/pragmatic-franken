<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Cli;

use App\Context\Task\Entity\Task;
use App\Context\Task\Repository\TaskRepository;
use App\SharedKernel\Domain\Env;
use K2gl\Component\AppEnv\Services\AppEnv;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Demo data for manual testing (`make db-seed`). Idempotent-ish: skips when
 * tasks already exist. Refused in prod — seeding production is an incident,
 * not a feature. Extend per project as new contexts appear (the CRM version
 * seeds users, products and prospects the same way).
 */
#[AsCommand(name: 'app:seed', description: 'Seed demo data for manual testing (dev only)')]
final class SeedCliCommand extends Command
{
    public function __construct(
        private readonly AppEnv $appEnv,
        private readonly TaskRepository $tasks,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnv->is(Env::Prod)) {
            $io->error('Refusing to seed the prod environment.');

            return Command::FAILURE;
        }

        if ($this->tasks->count() > 0) {
            $io->note('Tasks already present — nothing to seed.');

            return Command::SUCCESS;
        }

        $titles = [
            'Read AGENTS.md',
            'Run make smoke',
            'Scaffold a slice with make slice',
            'Complete this task via POST /tasks/{id}/complete',
            'Subscribe to /tasks live updates (see the Mercure guide)',
        ];

        foreach ($titles as $index => $title) {
            $task = new Task($title);

            if ($index < 2) {
                $task->complete();
            }

            $this->tasks->save($task, flush: false);
        }

        $this->tasks->flush();

        $io->success(sprintf('Seeded %d demo tasks (%d completed).', \count($titles), 2));

        return Command::SUCCESS;
    }
}
