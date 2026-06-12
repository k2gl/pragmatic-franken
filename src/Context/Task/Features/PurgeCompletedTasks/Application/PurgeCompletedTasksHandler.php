<?php

declare(strict_types=1);

namespace App\Context\Task\Features\PurgeCompletedTasks\Application;

use App\Context\Task\Repository\TaskRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;
use DateTimeImmutable;

/**
 * Reference symfony/scheduler task in worker mode: completed tasks older than
 * a week are purged hourly. The schedule is consumed by the worker container
 * (`messenger:consume scheduler_default`, enabled via SCHEDULER_ENABLED) —
 * see docker/docker-entrypoint.sh and docs/guides/worker-mode.md.
 */
#[AsPeriodicTask(frequency: '1 hour', schedule: 'default')]
final readonly class PurgeCompletedTasksHandler
{
    private const RETENTION = '-7 days';

    public function __construct(
        private TaskRepository $tasks,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): void
    {
        $purged = $this->tasks->deleteCompletedBefore(new DateTimeImmutable(self::RETENTION));

        if ($purged > 0) {
            $this->logger->info('Purged completed tasks.', ['count' => $purged]);
        }
    }
}
