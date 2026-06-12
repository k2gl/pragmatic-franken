# Upgrade notes for forks

Templates aren't merged — they're applied. Each release lists the few changes
worth porting into projects built from this skeleton, smallest diff first.
How-to: [`docs/guides/fork-maintenance.md`](docs/guides/fork-maintenance.md).

## Unreleased (the convention-alignment release)

Worth porting, smallest diff first:

1. **Pint ruleset** (`pint.json`): `new_with_parentheses {named_class:false}`,
   `single_line_empty_body`, `not_operator_with_successor_space`,
   `blank_line_before_statement`, `global_namespace_import`. Run `make lint`
   once, commit the reformat, add the SHA to `.git-blame-ignore-revs`.
2. **Slice anatomy** (ADR-0001): inside `Application/` the handler stays at the
   root; move `*Command`/`*Query` to `Application/Message/`, `*Request`/`*Result`
   and other DTOs to `Application/Dto/` (`git mv` + namespace line + imports).
   If you use `dev/generate-sdk.sh`, port its updated Result regex.
3. **HTTP response contract** (ADR-0016): success bodies wrap in
   `{"data": ...}`; input is a validated `*Request` DTO that the controller
   maps into a pure command. Errors/probes unchanged.
4. **PHPUnit 12**: `phpunit/phpunit ^12`, `k2gl/phpunit-fluent-assertions
   ^12.5`, XSD bump in `phpunit.xml`; replace expectation-less `createMock`
   with `createStub`.
5. **ADR renumbering**: supply-chain ADR is now 0014 (was 0018); new ADRs
   0015 (scheduler), 0016 (HTTP contract), 0017 (parallel agent sessions).
6. **Worker scheduler probe** (`docker/docker-entrypoint.sh`): the worker now
   checks `messenger.transport.scheduler_default` exists before consuming it —
   a fork with zero `#[AsPeriodicTask]` no longer crash-loops with
   `SCHEDULER_ENABLED=true`.
7. **`make test` idempotency**: `make test-db` now drops and recreates the
   test database before migrating — Foundry's schema reset empties the
   migration registry, so a bare re-migrate used to die with
   "relation already exists".
8. **Init fixes** (`dev/init.sh`): prune also removes `TaskFactory` and
   deletes only the example migration; init rewrites the composer description
   and refreshes the `composer.lock` content-hash after the rename.
9. **AGENTS.local.md pattern removed**: machine-local agent settings live in
   `.claude/settings.local.json`; `AGENTS.md` stays the single context file.

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
