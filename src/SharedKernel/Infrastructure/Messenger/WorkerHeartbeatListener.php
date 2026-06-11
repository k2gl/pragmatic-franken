<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Messenger;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

/**
 * Heartbeat for the `worker` container healthcheck: every Messenger worker
 * loop iteration refreshes a marker file; the compose healthcheck watches its
 * mtime (`find /tmp/worker-heartbeat -mmin -15`).
 */
final class WorkerHeartbeatListener
{
    #[AsEventListener]
    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        $this->beat();
    }

    #[AsEventListener]
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        $this->beat();
    }

    public static function heartbeatFile(): string
    {
        return sys_get_temp_dir().'/worker-heartbeat';
    }

    private function beat(): void
    {
        touch(self::heartbeatFile());
    }
}
