# 🚀 Pragmatic Franken

> **Why waste time on repetitive boilerplate?**
> Pragmatic Franken is a unified, production-ready foundation built on Symfony and FrankenPHP. Focus on logic, not configuration.

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777bb4?logo=php&logoColor=white)](https://www.php.net/releases/8.5/)
[![FrankenPHP 1.x](https://img.shields.io/badge/FrankenPHP-1.x-006b5b?logo=docker&logoColor=white)](https://frankenphp.dev/)
[![Symfony 7.2](https://img.shields.io/badge/Symfony-7.2-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![License MIT](https://img.shields.io/badge/License-MIT-yellowgreen)](https://opensource.org/licenses/MIT)
[![CI Pipeline](https://img.shields.io/github/actions/workflow/status/k2gl/pragmatic-franken/ci.yml?branch=main&label=CI)](https://github.com/k2gl/pragmatic-franken/actions)

---

## ⚡️ The Vibe

- **One Binary to Rule Them All**: No Nginx, no FPM. Just FrankenPHP.
- **Worker Mode by Default**: Boot once, handle thousands of requests.
- **AI-Native**: Pre-configured contexts for Cursor, Windsurf, and Copilot.
- **Pragmatic Architecture**: Vertical Slices instead of messy layers.

---

## 🚀 Instant Start

```bash
# 1. Clone and ignite
git clone https://github.com/k2gl/pragmatic-franken.git && cd pragmatic-franken

# 2. The Magic Command
make setup
```

**Boom!** Your app is live at https://localhost with automatic HTTPS.

---

## 🤖 AI-Driven Development

This repository is optimized for AI agents. We provide structured contexts so your AI assistant understands the architecture better than you do.

| File | Purpose |
|------|---------|
| [AGENTS.md](AGENTS.md) | Entry point for AI — core rules and patterns |
| [prompts/check-docs.md](prompts/check-docs.md) | Run this before any task to sync AI with ADRs |
| [.config/agents/agents.local.md](.config/agents/agents.local.md) | Your personal preferences (gitignored) |

**Pro tip:** Run `cat prompts/check-docs.md` to understand how AI validates documentation.

---

## 📦 The Pragmatic Stack

| Layer | Technology | Why? |
|-------|------------|------|
| Runtime | FrankenPHP | 103 Early Hints, Mercure, Go-speed. Single binary. |
| Architecture | Vertical Slices | Isolated features. High cohesion, low coupling. |
| CQRS | Messenger + Redis | Built-in async for background heavy lifting |
| Database | PostgreSQL 16 | Robust, modern, ACID-compliant |
| Cache | Redis 7 | Sessions, cache, Messenger transport |

---

## 📐 The Pragmatic Way

- **Slices over Layers** — Features first, not technical layers ([ADR 0001](docs/adr/0001-vertical-slices.md))
- **Commands over Classes** — Intent-driven code over abstract factories ([ADR 0002](docs/adr/0002-messenger-transport.md))
- **Safety over Cleverness** — Write code that survives Worker Mode restarts ([ADR 0006](docs/adr/0006-memory-management.md))

---

## ⚡️ Worker Mode Rules

**Write code that survives process restarts:**

| Rule | Bad | Good |
|------|-----|------|
| No static cache | `static $cache = []` | `$this->cache->set()` |
| Stateless entities | Entity with static state | Pure entities |
| Clean shutdown | Implicit memory leaks | `register_shutdown_function()` |
| Avoid singletons | `Singleton::getInstance()` | Dependency Injection |

See [Worker Mode Guide](docs/guides/worker-mode.md) for details.

---

## 🛠 Architecture Decisions (ADR)

Every decision is documented. No "because I said so".

| ADR | Topic | Priority |
|-----|-------|----------|
| [0001](docs/adr/0001-vertical-slices.md) | Vertical Slices Architecture | P0 |
| [0002](docs/adr/0002-messenger-transport.md) | Messenger Transport (CQRS) | P0 |
| [0003](docs/adr/0003-pragmatic-symfony-architecture.md) | Pragmatic Symfony | P0 |
| [0004](docs/adr/0004-frankenphp-runtime.md) | FrankenPHP Runtime | P1 |
| [0005](docs/adr/0005-health-checks.md) | Health Checks | P1 |
| [0006](docs/adr/0006-memory-management.md) | Memory Management | P2 |
| [0007](docs/adr/0007-asset-mapper.md) | AssetMapper | P2 |

[Read all ADRs →](docs/adr/)

---

## 📁 Project Structure

```
pragmatic-franken/
├── src/
│   ├── Kernel.php              # Symfony MicroKernel
│   ├── User/                   # Module (Bounded Context)
│   │   ├── Entity/
│   │   ├── Enums/
│   │   └── Features/           # Vertical Slices
│   │       ├── RegisterUser/
│   │       │   ├── RegisterUserCommand.php
│   │       │   ├── RegisterUserHandler.php
│   │       │   ├── EntryPoint/Http/
│   │       │   │   └── RegisterUserController.php
│   │       │   ├── Request/
│   │       │   └── Response/
│   │       └── Events/
│   │           └── UserRegisteredEvent.php
│   └── Shared/                 # Cross-module kernel
│       ├── Exception/
│       └── Services/
├── docker/
│   ├── frankenphp/            # FrankenPHP + Caddy
│   └── php/                   # Extensions
├── docs/
│   ├── adr/                   # Architecture Decisions
│   └── guides/                # How-to guides
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── EndToEnd/
├── Makefile
├── docker-compose.yml
└── prompts/                  # AI agent prompts and validation scripts
```

---

## 🐞 Debugging

- **Xdebug**: Port 9003, IDE key: PHPSTORM or VS Code
- **Health check**: http://localhost/healthz
- **Prometheus metrics**: http://localhost:2019

---

## 📚 Guides

- [Development Guide](docs/guides/development.md) — Quick start and daily commands
- [Testing Guide](docs/guides/testing.md) — Unit, Integration, E2E strategies
- [AI Agent Setup](docs/guides/agent-setup.md) — Configure Cursor, Windsurf, Copilot
- [Worker Mode Guide](docs/guides/worker-mode.md) — Writing safe async code

---

## 🤝 Contributing

See [Contributing Guidelines](.github/CONTRIBUTING.md) for details.
