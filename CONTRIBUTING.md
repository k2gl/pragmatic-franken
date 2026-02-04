# Contributing to Pragmatic Franken

Thank you for your interest in contributing! This guide will help you get started.

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

- All code must follow DDD patterns (see `AGENTS.md`)
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
├── src/           # Business logic
├── config/        # Symfony configuration
├── docs/          # Architecture decisions
├── tests/         # PHPUnit tests
├── docker/        # Docker configuration
├── Makefile       # Development commands
└── .github/       # CI/CD workflows
```

## Getting Help

- Check [docs/guides/](docs/guides/) for development guides
- Review [docs/architecture/](docs/architecture/) for architectural decisions
- Open an issue for questions
