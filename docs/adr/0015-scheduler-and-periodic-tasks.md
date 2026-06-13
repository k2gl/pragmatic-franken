---
id: ADR-0015
title: Scheduler & Periodic Tasks
status: Accepted
date: 2026-06-12
supersedes: []
superseded_by: []
audience: both
summary: "Recurring work is a slice handler with #[AsPeriodicTask] (symfony/scheduler), consumed by the same Messenger worker via the scheduler_default transport. SCHEDULER_ENABLED gates consumption in prod; the worker entrypoint probes for the transport so a fork with zero schedules never crash-loops."
---

# ADR-0015: Scheduler & Periodic Tasks

**TL;DR:** No crond in containers. A recurring job is ordinary slice code: an invokable handler annotated with `#[AsPeriodicTask]` (symfony/scheduler) — no separate message and no `#[AsMessageHandler]`; the scheduler invokes the service directly. The existing Messenger worker consumes `scheduler_default` next to `async`. The worker entrypoint verifies the transport exists before subscribing, so pruned forks (zero periodic tasks) keep working with the default `SCHEDULER_ENABLED=true`.

## Context

Containers ship no cron daemon, and host cron knows nothing about deploys, env or the DB. The work a "cron job" performs is feature logic and belongs in a slice (ADR-0001 §5); only the *trigger* is infrastructure. Symfony's scheduler turns schedules into messages on a dedicated transport, so the one worker process we already run can execute them — no extra daemon, no drift between environments.

## Decision

### 1. Periodic work is a slice handler

```php
// src/Context/Task/Features/PurgeCompletedTasks/Application/PurgeCompletedTasksHandler.php
#[AsPeriodicTask(frequency: '1 hour', schedule: 'default')]
final readonly class PurgeCompletedTasksHandler
{
    public function __invoke(): void
    {
        // ...
    }
}
```

The shipped reference is `src/Context/Task/Features/PurgeCompletedTasks/`. No message class and no `#[AsMessageHandler]` are needed — symfony/scheduler invokes the service directly. The schedule name is `default` (the attribute's default, shown explicitly above), which materialises as the `scheduler_default` transport.

### 2. The existing worker consumes the schedule

`docker/docker-entrypoint.sh` (the `worker` command) consumes `async scheduler_default` when `SCHEDULER_ENABLED=true`. Production compose defaults the flag to `true` (`docker/compose.prod.yml`); set it to `false` to split scheduling onto a dedicated worker replica later.

### 3. Fail-open probe for pruned forks

A fork created with `make init prune=1` has zero `#[AsPeriodicTask]` attributes, so the `scheduler_default` transport does not exist — consuming it would crash-loop the worker under the prod default. The entrypoint therefore probes first:

```sh
if php bin/console debug:container messenger.transport.scheduler_default >/dev/null 2>&1; then
    transports="async scheduler_default"
else
    echo "... no 'default' schedule is registered — consuming async only." >&2
fi
```

The first `#[AsPeriodicTask]` added to the fork re-enables consumption automatically on the next worker start. No env edits, no fork-time file surgery.

### 4. Overlap and recycling

One worker consumes the schedule, so runs of the same task do not overlap. The worker recycles via `--time-limit=3600` (ADR-0006); symfony/scheduler re-computes due tasks on restart. For multi-replica workers, add scheduler locking (`lock` resource on the schedule) before scaling out.

## Consequences

### Positive

- Recurring logic lives in slices, tested like any handler (see `PurgeCompletedTasksHandlerTest`).
- One process model: no crond, no extra container, schedules survive deploys.
- Pruned forks stay green and crash-free with stock settings.

### Negative

- A stopped worker means no schedules run — monitor the worker heartbeat (ADR-0009).
- `debug:container` probe at worker start adds one console boot (~hundreds of ms, once per container start).

## References

- ADR-0001 §5 — cron jobs are triggers, the work is a feature.
- ADR-0006 — worker recycling.
- `docker/docker-entrypoint.sh`, `docker/compose.prod.yml` — the consumption gate.
