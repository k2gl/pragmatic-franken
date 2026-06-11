# Pragmatic Franken

[![License MIT](https://img.shields.io/badge/License-MIT-yellowgreen)](https://opensource.org/licenses/MIT)
[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777bb4?logo=php&logoColor=white)](https://www.php.net/releases/8.5/)
[![FrankenPHP 1.x](https://img.shields.io/badge/FrankenPHP-1.x-006b5b?logo=docker&logoColor=white)](https://frankenphp.dev/)
[![Symfony 8](https://img.shields.io/badge/Symfony-8-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PHPStan Level 10](https://img.shields.io/badge/PHPStan-Level%2010-cyan)](https://phpstan.org)
[![CI](https://img.shields.io/github/actions/workflow/status/k2gl/pragmatic-franken/ci.yml?branch=main&label=CI)](https://github.com/k2gl/pragmatic-franken/actions)

PHP 8.5 / Symfony 8 / FrankenPHP starter that a production CRM actually grew
out of — and the lessons were ported back. Vertical Slices + CQRS over
Messenger, PostgreSQL 17, worker mode, real prod image, supply-chain
attestations. Designed to be operated by humans and AI agents alike.

**The promises are CI-proven, not vibes:**
- the **production image boots** on every PR (`prod-image` job: build → run → `/ready`);
- **scaffolded code passes PHPStan 10 + tests untouched** (`agent-smoke` job);
- release images carry **SLSA build provenance**, and the deploy gate verifies it (ADR-0018);
- docs are **linted against reality** (`make docs-check`: routes, Makefile targets, ADR sync).

For architecture rules and code-level guidance, see **[AGENTS.md](AGENTS.md)** — the single source of truth, ≤ 2 000 tokens, equally usable by developers and AI tools.

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

**Path 1 — template + Docker** (nothing but Docker needed):

```bash
gh repo create my-app --template k2gl/pragmatic-franken --clone && cd my-app
# or: git clone https://github.com/k2gl/pragmatic-franken.git my-app
make install                  # env, containers, deps, migrations
make init name=my-app         # rename + real secrets (optionally: prune=1 reset-git=1)
make smoke                    # bin/console + /ready
```

**Path 2 — composer create-project** (needs PHP ≥ 8.5 on the host):

```bash
composer create-project k2gl/pragmatic-franken my-app
cd my-app && make install && make init name=my-app
```

The app comes up at `https://my-app.localhost:${HTTPS_PORT:-4750}` (browsers
resolve `*.localhost` automatically — no `/etc/hosts` edits). Try the example
API: `POST /tasks`, `GET /tasks`, `POST /tasks/{id}/complete` — completion
pushes a Mercure live update on `/tasks`.

## Why not just symfony/skeleton?

| | **pragmatic-franken** | symfony/skeleton | dunglas/symfony-docker | API Platform |
|---|---|---|---|---|
| Architecture opinion | Vertical Slices + CQRS, 14 ADRs | none | none | API-first framework |
| Prod image | built, booted & scanned per PR | — | built | built |
| Real example vertical | entity→migration→factory→tests | — | — | generated CRUD |
| Deploy story | blue-green to a VDS + backups + DR drill | — | — | k8s helm |
| Supply chain | attestations + offline verifier + gate | — | — | — |
| Agent affordances | AGENTS.md (≤2k tokens) + CI-proven scaffolding | — | — | — |
| Best when | product API/app on one VDS, agents in the loop | you want vanilla | you want plain Docker | your product *is* the API |

Honest non-goals: no auth in core (recipe instead), no bundled SPA, no
Kubernetes, no multi-DB. See [`docs/roadmap.md`](docs/roadmap.md#non-goals).

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
| `make docs-check` | lint docs against reality (routes, targets, budgets) |
| `make agent-smoke` | prove scaffolded code passes all gates untouched |
| `make db-seed` | demo data (`app:seed`) |

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
- [`docs/guides/supply-chain.md`](docs/guides/supply-chain.md) — sign releases, verify before deploying
- [`docs/roadmap.md`](docs/roadmap.md) — roadmap

## Recipes (opt-in capabilities)

Proven in the production CRM grown from this skeleton, documented instead of
bundled: [JWT auth](docs/recipes/jwt-auth.md) ·
[feature flags](docs/recipes/feature-flags.md) ·
[SPA frontend](docs/recipes/spa-frontend.md) ·
[preview environments](docs/recipes/preview-environments.md).

## Staying close to the template

Forks don't merge templates — they apply changes. Each release ships an
[`UPGRADE.md`](UPGRADE.md) entry with the few changes worth porting; see
[`docs/guides/fork-maintenance.md`](docs/guides/fork-maintenance.md).

## AI agents

The repository ships with a single `AGENTS.md` at the root, intended to be read by every AI tool by convention. There are no `.cursorrules`, `.windsurfrules`, `.cursor/rules/*` or per-tool prompt files — see [ADR-0010](docs/adr/0010-documentation-and-ai-layout.md) for the rationale.

For per-developer overrides (tone, language, IDE quirks), copy `AGENTS.local.md.example` to `AGENTS.local.md` (gitignored).

## Contributing

See [`.github/CONTRIBUTING.md`](.github/CONTRIBUTING.md). Conventional Commits required for the message header. CI gates: Pint, PHPStan level 10, PHPUnit. `make ci` mirrors the pipeline locally.

## License

MIT.
