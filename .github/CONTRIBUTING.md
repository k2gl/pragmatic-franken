# Contributing to Pragmatic Franken

First of all, thank you for wanting to make the PHP world better! 🚀

## How to Help

1. **Follow the ADRs**: If you want to change something architectural, please open a Discussion first. We follow the decisions in `docs/adr/`.
2. **AI-Ready Code**: If you add new features, ensure they are "understandable" for AI (proper types, attributes, and no hidden magic).
3. **PR Style**:
   - Keep it small.
   - Mention which ADR it relates to.
   - Add a "vibe" emoji to your PR title (e.g., `⚡️ feature: add redis healthcheck`).

## Quick Start

1. **Fork the repository**
2. **Create a feature branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** following the [Architecture Guidelines](docs/architecture/)
4. **Run tests:**
   ```bash
   make test
   ```
5. **Ensure lint passes:**
   ```bash
   make lint
   ```
6. **Commit using Conventional Commits:**
   ```bash
   git commit -m "feat: add new feature description"
   ```
7. **Push and create a Pull Request**

## Code Standards

- All code must follow Vertical Slices architecture (see [AGENTS.md](AGENTS.md))
- PHP 8.5+ with strict typing
- All tests must pass
- PHPStan level 8 compliance

## Conventional Commits

We use [Conventional Commits](https://www.conventionalcommits.org/) for clear changelog generation:

| Type | Description |
|------|-------------|
| `feat:` | New feature |
| `fix:` | Bug fix |
| `docs:` | Documentation changes |
| `style:` | Code style changes (formatting, etc.) |
| `refactor:` | Code refactoring |
| `test:` | Adding or modifying tests |
| `chore:` | Maintenance tasks |
| `perf:` | Performance improvements |
| `ci:` | CI/CD changes |
| `build:` | Build system changes |

## Project Structure

```
pragmatic-franken/
├── src/
│   ├── Kernel.php              # Symfony MicroKernel
│   ├── User/                   # Module (Bounded Context)
│   │   ├── Features/          # Feature Slices
│   │   ├── Entity/
│   │   └── Repository/
│   └── Shared/                # Cross-module Shared Kernel
├── config/                     # Symfony configuration
├── docker/
│   ├── frankenphp/            # FrankenPHP config
│   └── php/                   # PHP extensions
├── docs/                       # Architecture decisions
├── tests/                      # PHPUnit tests
├── .github/
│   ├── workflows/             # CI/CD pipelines
│   └── CONTRIBUTING.md         # This file
├── Caddyfile                  # FrankenPHP server config
├── docker-compose.yml
├── Makefile
└── .config/agents/            # AI agent configurations
```

## Getting Help

- Check [docs/guides/](docs/guides/) for development guides
- Review [docs/architecture/](docs/architecture/) for architectural decisions
- Open an issue for questions
