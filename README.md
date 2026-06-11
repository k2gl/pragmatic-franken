# Pragmatic Franken

[![License MIT](https://img.shields.io/badge/License-MIT-yellowgreen)](https://opensource.org/licenses/MIT)
[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777bb4?logo=php&logoColor=white)](https://www.php.net/releases/8.5/)
[![FrankenPHP 1.x](https://img.shields.io/badge/FrankenPHP-1.x-006b5b?logo=docker&logoColor=white)](https://frankenphp.dev/)
[![Symfony 8.0](https://img.shields.io/badge/Symfony-8.0-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PHPStan Level 10](https://img.shields.io/badge/PHPStan-Level%2010-cyan)](https://phpstan.org)
[![CI](https://img.shields.io/github/actions/workflow/status/k2gl/pragmatic-franken/ci.yml?branch=main&label=CI)](https://github.com/k2gl/pragmatic-franken/actions)

PHP 8.5 / Symfony 8 / FrankenPHP boilerplate. Vertical Slices + CQRS over Symfony Messenger. PostgreSQL 17, Redis 7. Designed to be readable by both humans and AI agents.

For architecture rules and code-level guidance, see **[AGENTS.md](AGENTS.md)** — it is the single source of truth, ≤ 2 000 tokens, equally usable by developers and AI tools.

## Stack

| Layer | Choice | Why |
|---|---|---|
| Application server | FrankenPHP 1.x (Caddy + worker mode) | one binary, HTTP/3, kernel kept hot |
| Framework | Symfony 8.0 | mature, attribute-based wiring |
| Bus | Symfony Messenger (CQRS) | sync commands/queries, async events |
| Database | PostgreSQL 17 | Doctrine ORM 3 |
| Queue | Messenger on Doctrine transport | no extra extension, inspectable, CRM-proven |
| Cache | Redis 7 (Predis) | ready for caching and custom needs |
| Frontend (default) | AssetMapper + Twig | no Webpack/Vite needed for HTML-first |
| Code style | Laravel Pint (PSR-12) | one-flag autofix |
| Static analysis | PHPStan level 10 | enforced in CI |
| Tests | PHPUnit 11 + Zenstruck (Foundry, Browser, Messenger-Test) + DAMA + Faker + Fluent Assertions | pyramid 60/30/10 |

## Quickstart

```bash
git clone https://github.com/k2gl/pragmatic-franken.git
cd pragmatic-franken
make install        # env, containers, deps, migrations
make smoke          # bin/console list && /healthz
```

The app comes up at `https://pragmatic-franken.localhost:${HTTPS_PORT:-4750}`. Caddy resolves `*.localhost` automatically — no `/etc/hosts` edits.

## Daily commands

| Command | Effect |
|---|---|
| `make up` / `make down` | start / stop containers |
| `make shell` (alias `make e`) | shell inside the app container |
| `make test` | PHPUnit, fail-fast |
| `make check` | Pint + PHPStan (pre-commit gate) |
| `make ci` | lint-check + analyze + test (CI parity) |
| `make smoke` | end-to-end smoke check |
| `make slice context=Foo feature=Bar` | scaffold a vertical slice |
| `make adr title="My Decision"` | scaffold a new ADR |
| `make docs-check` | lint ADR front-matter & AGENTS.md budget |

## Project layout

```
src/                              # application code
  Kernel.php                      # App\Kernel (Symfony MicroKernel)
  SharedKernel/                   # cross-context infra (repository base, problem+json listeners)
  Context/{Name}/                 # bounded contexts: Entity/, Repository/, Features/{Feature}/
    Health/Features/Healthz/      # reference slice (JSON, /healthz + /ready)
    Home/Features/Index/          # reference slice (Twig + AssetMapper, /)
config/  bin/  public/  assets/   # standard Symfony layout
migrations/                       # Doctrine migrations
docs/                             # ADRs and guides (Tier 2)
  adr/                            # ADRs with YAML front-matter
  guides/                         # development, testing, worker-mode, …
dev/                              # codegen helpers (create-slice, new-adr, check-docs)
ops/                              # deployment scripts
tests/                            # mirrors src/ — tests/Context/{Name}/Features/{Feature}/
docker/                           # Dockerfile, Caddyfile, php.ini
AGENTS.md                         # Tier-1 agent context, ≤ 2 000 tokens
AGENTS.local.md.example           # per-developer overrides template (gitignored target)
```

The example slices (`Health/Healthz`, `Home/Index`, `Task`) are reference implementations — `Healthz` is normative for ADR-0005 health checks, `Task` for the full entity → migration → factory → tests vertical; `Home/Index` is non-normative (drop it for API-only or SPA projects).

## Architecture decisions

All decisions live in [`docs/adr/`](docs/adr/). Each ADR carries YAML front-matter (`status`, `date`, `audience`, `summary`) so agents can skim without loading the full body.

| ADR | Topic | Status |
|---|---|---|
| [0001](docs/adr/0001-vertical-slices.md) | Vertical Slices | Accepted |
| [0002](docs/adr/0002-messenger-transport.md) | Messenger Transport | Accepted |
| [0003](docs/adr/0003-pragmatic-symfony-architecture.md) | Pragmatism Charter | Accepted |
| [0004](docs/adr/0004-frankenphp-runtime.md) | FrankenPHP Runtime | Accepted |
| [0005](docs/adr/0005-health-checks.md) | Health Checks | Accepted |
| [0006](docs/adr/0006-memory-management.md) | Memory Management | Accepted |
| [0007](docs/adr/0007-asset-mapper.md) | AssetMapper | Accepted |
| [0008](docs/adr/0008-testing-strategy.md) | Testing Strategy | Accepted |
| [0009](docs/adr/0009-shared-architecture.md) | Shared Architecture | Accepted |
| [0010](docs/adr/0010-documentation-and-ai-layout.md) | Documentation & AI Layout | Accepted |
| [0011](docs/adr/0011-event-sourcing-lite.md) | Event Sourcing Lite | Accepted |
| [0012](docs/adr/0012-ubiquitous-language.md) | Ubiquitous Language & Entity Placement | Accepted |
| [0013](docs/adr/0013-doctrine-repository-pattern.md) | Doctrine Repository Pattern | Accepted |
| [0018](docs/adr/0018-supply-chain-security.md) | Supply-Chain Security | Accepted |

## Guides

- [`docs/guides/development.md`](docs/guides/development.md) — daily commands, slice scaffolding details
- [`docs/guides/testing.md`](docs/guides/testing.md) — concrete PHPUnit + Foundry + Messenger-Test patterns
- [`docs/guides/worker-mode.md`](docs/guides/worker-mode.md) — debugging FrankenPHP worker behavior
- [`docs/guides/mercure-integration.md`](docs/guides/mercure-integration.md) — real-time SSE via FrankenPHP's built-in Mercure hub
- [`docs/guides/sdk-generation.md`](docs/guides/sdk-generation.md) — auto-generate TypeScript types from PHP Result DTOs
- [`docs/guides/deployment.md`](docs/guides/deployment.md) — single-VDS topology, zero-downtime rollout
- [`docs/guides/disaster-recovery.md`](docs/guides/disaster-recovery.md) — backups and the restore drill
- [`docs/guides/parallel-sessions.md`](docs/guides/parallel-sessions.md) — isolated worktree stacks for parallel agents
- [`docs/roadmap.md`](docs/roadmap.md) — roadmap

## AI agents

The repository ships with a single `AGENTS.md` at the root, intended to be read by every AI tool by convention. There are no `.cursorrules`, `.windsurfrules`, `.cursor/rules/*` or per-tool prompt files — see [ADR-0010](docs/adr/0010-documentation-and-ai-layout.md) for the rationale.

For per-developer overrides (tone, language, IDE quirks), copy `AGENTS.local.md.example` to `AGENTS.local.md` (gitignored).

## Contributing

See [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md). Conventional Commits required for the message header. CI gates: Pint, PHPStan level 10, PHPUnit. `make ci` mirrors the pipeline locally.

## License

MIT.
