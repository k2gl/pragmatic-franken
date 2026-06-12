# Contributing to Pragmatic FrankenPHP

Thank you for wanting to make the PHP world better! 🚀

## How to help

1. **Follow the ADRs.** Architectural changes start with a Discussion; the
   decisions live in [`docs/adr/`](../docs/adr/).
2. **AI-ready code.** New code must be understandable by agents and humans
   alike: precise types, attributes, no hidden magic ([AGENTS.md](../AGENTS.md)).
3. **Keep PRs small** and mention the ADR they relate to.

## Quick start

```bash
# fork, then:
git clone git@github.com:<you>/pragmatic-franken.git
cd pragmatic-franken
make install          # env, containers, deps, migrations
git checkout -b feat/your-change
# ...hack...
make ci               # lint-check + analyze + test — must be green
make docs-check       # docs must match reality
git commit -m "feat: describe your change"
```

## Ground rules

- Vertical Slices: feature code goes to `src/{Context}/Features/{Feature}/`
  (see [AGENTS.md](../AGENTS.md) and ADR-0001).
- PHP 8.5+, `declare(strict_types=1)` everywhere (Pint enforces it).
- PHPStan **level 10** — no baseline, no ignores without a comment.
- Tests follow the pyramid of ADR-0008; e2e tests live next to the slice
  they cover, mirrored under `tests/`.

## Commit messages

PR titles and commits follow [Conventional Commits](https://www.conventionalcommits.org/)
(enforced by CI; release-please builds the changelog from them):

`feat:` `fix:` `docs:` `style:` `refactor:` `test:` `chore:` `perf:` `ci:` `build:`

## Getting help

- [`docs/guides/`](../docs/guides/) — development, testing, worker mode, deployment
- [`docs/adr/`](../docs/adr/) — architectural decisions
- Open an issue or a Discussion for anything else
