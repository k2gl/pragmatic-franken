# Upgrade notes for forks

Templates aren't merged — they're applied. Each release lists the few changes
worth porting into projects built from this skeleton, smallest diff first.
How-to: [`docs/guides/fork-maintenance.md`](docs/guides/fork-maintenance.md).

## v1.0.0 (the honesty release)

If your fork predates v1.0.0, port at minimum:

1. **Prod image fixes** (`docker/frankenphp/Dockerfile`): `composer
   dump-autoload --no-dev --classmap-authoritative` in `php_prod`, `COPY bin
   bin/` + `COPY migrations migrations/` + `touch .env`, drop any
   `APP_RUNTIME` override (symfony/runtime 8 drives FrankenPHP natively).
   Add the `prod-image` CI job — it would have caught all of this.
2. **Single env truth**: add `symfony/dotenv`, remove app config duplicated in
   compose `environment:`, bind db/redis ports to loopback with var-driven
   offsets, test DB via `dbname_suffix: '_test'` instead of sqlite.
3. **Probes**: `/healthz` = liveness (no dependency calls), `/ready` =
   readiness (DB+Redis pings); point compose healthcheck, smoke and deploy
   gates at `/ready`.
4. **Messenger transport**: `doctrine://default?auto_setup=true` (the Redis
   DSN required ext-redis that was never installed).
5. **docs lint**: take `dev/check-docs.sh` — it verifies make targets, routes
   and ADR tables against reality on every CI run.
