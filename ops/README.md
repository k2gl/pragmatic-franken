# ops/

Deployment and SRE-facing scripts. Audience: CI/CD pipelines and operators.
Topology and one-time VDS setup: `docs/guides/deployment.md`.

| Script | Purpose |
|---|---|
| `deploy.sh` | Deploy one environment (stage/prod) from its checkout: fetch ref, build `php_prod`, pre-migration dump (prod), zero-downtime rollout. |
| `rollout.sh` | Blue-green swap of one compose service gated on `/ready`; a failing new container never replaces the old one. |
| `backup.sh` | create / list / restore — timestamped archive (pg_dump + env), local retention, encrypted off-site via rclone. See `docs/guides/disaster-recovery.md`. |
