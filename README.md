# Pragmatic Franken

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777bb4?logo=php&logoColor=white)](https://www.php.net/releases/8.5/)
[![FrankenPHP](https://img.shields.io/badge/FrankenPHP-1.x-006b5b?logo=docker&logoColor=white)](https://frankenphp.dev/)
[![Symfony 7.2](https://img.shields.io/badge/Symfony-7.2-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![License MIT](https://img.shields.io/badge/License-MIT-yellowgreen)](https://opensource.org/licenses/MIT)
[![CI Pipeline](https://img.shields.io/github/actions/workflow/status/k2gl/pragmatic-franken/ci.yml?branch=main&label=CI)](https://github.com/k2gl/pragmatic-franken/actions)

> Symfony project with FrankenPHP, DDD patterns, and Docker infrastructure.

## 🎯 Key Features

- **Modular Architecture** — DDD patterns with clear module boundaries
- **AI-Ready** — AGENTS.md for AI assistant configuration
- **Developer Experience** — `make up` for quick start

## 🛠 Technologies

- **PHP 8.5 (Alpine)**: Latest features (Pipe operator, URI extension).
- **FrankenPHP**: Go-based application server with Worker Mode support.
- **PostgreSQL 16**: Primary database.
- **Redis 7**: Cache, sessions, and Messenger.
- **Caddy**: Automatic HTTPS and HTTP/3.

## 📊 FrankenPHP Features

- Worker Mode for long-running processes without separate queues
- 103 Early Hints for resource preloading
- Built-in Mercure for real-time updates
- Prometheus metrics on port 2019

See [ADR 002: FrankenPHP Runtime](docs/adr/adr-002-frankenphp-runtime.md) for details.

## 🚀 Quick Start

1. **Start the project:**
   ```bash
   make up
   ```

2. **Install dependencies:**
   ```bash
   make install
   ```

3. **Run migrations:**
   ```bash
   make db-migrate
   ```

Project will be available at: https://localhost (or http://localhost).

## 🤖 AI Agent Integration

This project is optimized for AI assistants (Cursor, Windsurf, GitHub Copilot).

### Local Environment Setup

For the agent to know paths to your local tools (Git CLI, Python, etc.) and follow your personal preferences:

1. Navigate to `.config/agents/` directory.
2. Copy `agents.local.md.example` as `agents.local.md`.
3. Make your edits (this file is in `.gitignore` and won't be committed).

### Configuration Structure

- `AGENTS.md` (root) — entry point for AI.
- `.config/agents/agents.md` — project rules and code standards.
- `.config/agents/agents.local.md` — your personal settings and tool paths.

## 📁 Project Structure

```
pragmatic-franken/
├── src/
│   ├── Kernel.php              # Symfony MicroKernel
│   ├── User/                   # Module (Bounded Context)
│   │   ├── Entity/             # Doctrine Entities
│   │   ├── Enums/
│   │   └── Features/            # Vertical Slices
│   │       ├── RegisterUser/
│   │       │   ├── RegisterUserCommand.php      # Command
│   │       │   ├── RegisterUserHandler.php      # Handler
│   │       │   ├── EntryPoint/Http/
│   │       │   │   └── RegisterUserController.php
│   │       │   ├── Request/
│   │       │   └── Response/
│   │       └── Events/
│   │           └── UserRegisteredEvent.php       # Domain Event
│   ├── Task/                   # Task management module
│   ├── Board/                  # Board management module
│   └── Shared/                 # Cross-module Shared Kernel
│       ├── Exception/
│       └── Services/
├── config/                     # Symfony configuration
├── docker/
│   ├── frankenphp/            # FrankenPHP config + Caddyfile
│   ├── php/                   # PHP extensions
│   └── ...
├── docs/                       # Architecture decisions (ADRs) and guides
│   ├── adr/                    # Architectural Decision Records
│   │   ├── 0001-vertical-slices.md       # Folder Structure (P0)
│   │   ├── 0002-messenger-transport.md  # CQRS Pattern (P0)
│   │   ├── 0003-pragmatic-symfony-architecture.md  # Core Architecture (P0)
│   │   ├── 0004-frankenphp-runtime.md    # Server Runtime (P1)
│   │   ├── 0005-health-checks.md         # Production Health (P1)
│   │   ├── 0006-memory-management.md     # Performance (P2)
│   │   └── 0007-asset-mapper.md          # Frontend Assets (P2)
│   ├── architecture/           # Architecture documentation
│   │   └── vertical-slices.md
│   └── guides/                 # Development guides
├── tests/                      # PHPUnit tests
│   ├── Unit/
│   ├── Integration/
│   └── EndToEnd/
├── .github/
│   ├── workflows/             # CI/CD pipelines
│   └── CONTRIBUTING.md        # Contribution guidelines
├── Caddyfile                  # FrankenPHP server config (symlinked)
├── docker-compose.yml
├── Makefile
├── AGENTS.md                  # AI Agent entry point
└── .config/agents/            # AI Agent configurations
```

## 🔄 Application Flow

```mermaid
flowchart TD
    A[HTTP Request] --> B[Controller / EntryPoint]
    B --> C[Command / Query]
    C --> D[Handler]
    D --> E[Entity / Domain]
    D --> F[Repository]
    E --> G[Domain Event]
    G --> H[Event Bus]
    H --> I[Async Handlers]
    H --> J[Mercure / Real-time]

    subgraph Persistence
        F --> K[(PostgreSQL)]
    end

    subgraph Cache
        D --> L[(Redis)]
    end
```

## 🏗 Docker Architecture

Multi-stage build is used:
- **php_base**: Base layer with extensions (intl, bcmath, pdo_pgsql, apcu).
- **php_dev**: Development layer (Xdebug, dev dependencies).
- **php_prod**: Optimized layer for production (Worker Mode, Preload, AssetMapper).

## 🛡 CI/CD Pipeline

On each push to main, GitHub Actions performs:

1. **Gitleaks**: Search for secrets in code.
2. **Composer Audit**: Check for vulnerabilities in PHP packages.
3. **Trivy**: Scan image for system vulnerabilities.
4. **PHPStan**: Static analysis (Level 8).
5. **PHP-CS-Fixer**: Code style enforcement.
6. **PHPUnit**: Run tests.

## 📊 Monitoring and Metrics

- **Prometheus**: Collects FrankenPHP metrics on port 2019.
- **Grafana**: Visualization (port 3000).
- **Healthcheck**: Container automatically restarts if /healthz endpoint is unavailable.

## ⏰ Scheduler (Cron)

Tasks are executed via Symfony Scheduler inside the main FrankenPHP container. Process management is handled via exec in Caddyfile.

## 🐞 Debugging (Xdebug)

- Xdebug configured on port 9003.
- Host: host.docker.internal
- IDE Key: PHPSTORM or VS Code "PHP Debug" extension.

## 📚 Documentation

### Architecture Decisions (ADRs)

All major architectural decisions are documented as ADRs in [`docs/adr/`](docs/adr/):

- [ADR 001: Pragmatic Symfony Architecture](docs/adr/adr-001-pragmatic-symfony-architecture.md) — Core principles, Message Bus Rule, Modular Architecture
- [ADR 002: FrankenPHP Runtime](docs/adr/adr-002-frankenphp-runtime.md) — Server selection, Stateless Design
- [ADR 003: AssetMapper](docs/adr/adr-003-asset-mapper.md) — Frontend asset management
- [ADR 004: Messenger Transport](docs/adr/adr-004-messenger-transport.md) — Command/Query separation, AI use cases
- [ADR 005: Health Checks](docs/adr/adr-005-health-checks.md) — Docker/K8s probes, PHP endpoints
- [ADR 006: Memory Management](docs/adr/adr-006-memory-management.md) — PHP/Docker/Worker configuration for AI

See [Architecture Overview](docs/ARCHITECTURE.md) for implementation details.

### Guides

- [Development Guide](./docs/guides/development.md) — Quick start and daily commands
- [Testing Guide](./docs/guides/testing.md) — Unit, Integration, E2E strategies
- [AI Agent Setup](./docs/guides/agent-setup.md) — Configure Cursor, Windsurf, Copilot
- [Local Agent Config](./docs/guides/agents-local-config.md) — Personal preferences for AI
- [AI Automation](./docs/guides/ai-automation.md) — Free AI tools for repository maintenance

### Contribution

- [Contributing Guidelines](./.github/CONTRIBUTING.md) — How to contribute

## 🔧 Automation & AI Tools

The project is configured with:

- **Conventional Commits**: Automatic PR validation
- **Dependabot**: Auto-update dependencies
- **CodiumAI**: Free PR reviews (setup required)

See [AI Automation Guide](./docs/guides/ai-automation.md) for setup instructions.
