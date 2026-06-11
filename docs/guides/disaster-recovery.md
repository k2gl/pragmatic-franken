---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "Recovery from off-site backups: ops/backup.sh create/list/restore, encrypted rclone remote, monthly restore drill. An untested backup is not a backup."
---

# Disaster Recovery

The VDS is a single point of failure. The recovery objects are: the database
dump and the environment's `.env` secrets — both inside one timestamped
archive produced by `ops/backup.sh` (see its header for cron setup).

## What is backed up

`app-<env>-<ts>.tar.gz` = `db.dump` (pg_dump -Fc) + `.env.<env>` + manifest
(git SHA, pg version). Local copies rotate after 14 days; off-site copies live
on an **encrypted** rclone remote (`crypt:`) and rotate after 30 days.
A dump on the same disk is not a backup.

## Restore — same host

```bash
cd /srv/app/prod
ENVIRONMENT=prod ./ops/backup.sh list
ENVIRONMENT=prod ./ops/backup.sh restore latest          # DB only (safety dump taken first)
ENVIRONMENT=prod ./ops/backup.sh restore <name> --with-secrets   # DB + .env, restarts stack
```

## Restore — new host (total loss)

1. Provision the VDS: docker, `docker network create app-shared`, clone the
   repo into `/srv/app/prod`, set up the proxy (`docs/guides/deployment.md`).
2. Configure the same rclone remote (`rclone config`) — credentials live with
   the owner, not on the dead host.
3. `ENVIRONMENT=prod ./ops/backup.sh restore latest --with-secrets`
4. `ENVIRONMENT=prod ./ops/deploy.sh` and point DNS at the new IP.

## The drill

Monthly, restore `latest` into a throwaway database and run one meaningful
query. Put it in the calendar. An untested backup is not a backup — this
sentence is repeated on purpose.
