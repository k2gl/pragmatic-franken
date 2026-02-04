# Pragmatic Franken

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777bb4?logo=php&logoColor=white)](https://www.php.net/releases/8.5/)
[![FrankenPHP](https://img.shields.io/badge/FrankenPHP-1.x-006b5b?logo=docker&logoColor=white)](https://frankenphp.dev/)
[![Symfony 7.2](https://img.shields.io/badge/Symfony-7.2-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![License MIT](https://img.shields.io/badge/License-MIT-yellowgreen)](https://opensource.org/licenses/MIT)
[![CI Pipeline](https://img.shields.io/github/actions/workflow/status/k2gl/pragmatic-franken/conventional-commits.yml?branch=main&label=CI)](https://github.com/k2gl/pragmatic-franken/actions)

> A no-compromise skeleton template for building high-performance PHP applications. Combines the flexibility of Symfony with the power of FrankenPHP in a perfectly configured Docker infrastructure.

## 🎯 Why This Template?

| For CTOs | For Tech Leads | For Developers |
|----------|---------------|-----------------|
| 2-3x performance boost over PHP-FPM | Architecture enforcement via DDD patterns | Instant onboarding with `make up` |
| 30-50% faster perceived load with 103 Early Hints | AI-ready codebase with AGENTS.md | Clear patterns (Command/Query/Event) |
| Reduced infrastructure costs | Modular monolith ready for microservices | Pre-configured Docker + Xdebug |

## 🛠 Technologies

- **PHP 8.5 (Alpine)**: Latest features (Pipe operator, URI extension).
- **FrankenPHP**: Go-based application server with Worker Mode support.
- **PostgreSQL 16**: Primary database.
- **Redis 7**: Cache, sessions, and Messenger.
- **Caddy**: Automatic HTTPS and HTTP/3.

## 📊 Performance Comparison

| Metric | Traditional PHP-FPM | **This Template (FrankenPHP)** |
|--------|---------------------|------------------------------|
| Requests/sec | ~500 | **~1,500** |
| Memory usage | ~256MB | **~128MB** |
| Cold start | 500ms | **50ms** |
| Worker Mode | Separate process | **Native** |
| 103 Early Hints | Not supported | **Supported** (30-50% faster perceived load) |
| Separate services needed | PHP-FPM + Caddy + Supervisor | **Single binary** |

See [Why FrankenPHP?](docs/why-frankenphp.md) for detailed benchmarks.

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
│   │   ├── Features/          # Vertical Slices
│   │   │   ├── RegisterUser/
│   │   │   │   ├── RegisterUserAction.php       # Controller
│   │   │   │   ├── RegisterUserMessage.php      # DTO
│   │   │   │   ├── RegisterUserHandler.php       # Business Logic
│   │   │   │   └── RegisterUserResponse.php      # Response
│   │   │   └── Events/
│   │   │       └── UserRegisteredEvent.php       # Domain Event
│   │   ├── Entity/
│   │   │   └── User.php
│   │   └── Repository/
│   └── Shared/                 # Cross-module Shared Kernel
│       ├── Messaging/          # Messenger config
│       └── EventBus.php
├── config/                     # Symfony configuration
├── docker/
│   ├── frankenphp/            # FrankenPHP config + Caddyfile
│   ├── php/                   # PHP extensions
│   └── ...
├── docs/                       # Architecture decisions and guides
├── tests/                      # PHPUnit tests
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
    A[HTTP Request] --> B[Action / Controller]
    B --> C[Message / Command]
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

See `/docs/` folder for:

### Architecture
- **why-frankenphp.md**: Performance benefits, 103 Early Hints, Mercure integration.
- **why-modular-monolith.md**: How to separate modules for future microservices.
- **decision-on-outbox.md**: Guaranteed event delivery patterns.

### Guides
- **development.md**: Quick start and daily commands.
- **testing.md**: Testing strategies (Unit, Integration, E2E).
- **agent-setup.md**: AI agent configuration.
- **agents-local-config.md**: Personal preferences for AI agents.
- **ai-automation.md**: Free AI tools for repository maintenance.

## 🔧 Automation & AI Tools

The project is configured with:

- **Conventional Commits**: Automatic PR validation.
- **Dependabot**: Auto-update dependencies.
- **CodiumAI**: Free PR reviews (setup required).

See `docs/guides/ai-automation.md` for setup instructions.
