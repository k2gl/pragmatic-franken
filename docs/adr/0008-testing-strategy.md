---
id: ADR-0008
title: Testing Strategy
status: Accepted
date: 2026-04-27
supersedes: []
superseded_by: []
audience: both
summary: "PHPUnit 11 with mirror-of-src test layout; pyramid 60/30/10 (unit/integration/e2e); coverage thresholds Domain 90 / Application 80 / Infrastructure 60 / UI 40."
---

# ADR-0008: Testing Strategy

**TL;DR:** PHPUnit 11 is the test framework. Tests mirror `src/` one-to-one. Test type is encoded by the base class plus a `#[Group]` attribute, not by a top-level directory. Coverage gates are enforced per layer (Domain 90 / Application 80 / Infrastructure 60 / UI 40).

## Context

Choosing a testing strategy for a PHP/Symfony/FrankenPHP project affects developer productivity, the predictability of AI-generated code, CI cost, and how easy it is to delete a feature. We considered PHPUnit and Pest; we considered three layouts (separated `Unit/Integration/EndToEnd` directories, mirror-of-`src/` with grouping, and feature-local tests next to handlers).

## Decision

1. **Framework: PHPUnit 11.** Mature, deeply integrated with Symfony Test framework, abundant AI training data, full PHPStan compatibility. Pest is rejected for this boilerplate (less AI training data, extra plugin layer with Symfony).
2. **Layout: mirror of `src/`** at `tests/{Module}/Features/{Feature}/`. Test *type* is communicated via the base class (`UnitTestCase` / `IntegrationTestCase` / `ApiTestCase`) and PHPUnit `#[Group]` attribute (`unit` / `integration` / `e2e`).
3. **Pyramid: 60 / 30 / 10** (unit / integration / e2e). Most logic is exercised in cheap unit tests; integration covers persistence, Messenger, and external adapters; e2e validates HTTP contracts.
4. **Coverage thresholds** (enforced in CI):

| Layer | Path glob | Minimum line coverage |
|---|---|---|
| Domain | `src/*/Features/*/Domain/`, `src/*/Domain/` | **90 %** |
| Application | `src/*/Features/*/Application/` | **80 %** |
| Infrastructure | `src/*/Features/*/Infrastructure/`, `src/*/Infrastructure/` | **60 %** |
| UI / EntryPoint | `src/*/Features/*/EntryPoint/` | **40 %** |

5. **Async support:** `zenstruck/messenger-test` for in-memory bus assertions; `zenstruck/foundry` + `dama/doctrine-test-bundle` for database isolation.

## Test layout

```
tests/
├── bootstrap.php
├── Support/                                  # framework helpers, not tests
│   ├── TestCase/{UnitTestCase, IntegrationTestCase, ApiTestCase}.php
│   ├── Factory/                              # Foundry factories
│   └── Helper/
└── {Module}/Features/{Feature}/{Feature}HandlerTest.php
```

| Test type | Base class | `#[Group]` | Make target |
|---|---|---|---|
| Unit | `UnitTestCase` | `unit` | `make test-unit` |
| Integration | `IntegrationTestCase` | `integration` | `make test-integration` |
| API / E2E | `ApiTestCase` | `e2e` | `make test-e2e` |

Concrete examples live in `docs/guides/testing.md` (this ADR is the source of truth for the *decision*; the guide is the source of truth for *patterns*).

## Consequences

### Positive

- One folder per feature for both production and test code → deletion is one `rm -rf`.
- Test type is searchable (`grep -r '#[Group(\'integration\')]'`).
- Coverage gates pin domain rigor where it matters and avoid overspending on UI tests.
- AI agents reliably scaffold mirror paths.

### Negative

- Test runners that filter only by directory cannot select "all unit tests" in one shot — must use `--group=unit`. Mitigated by Make targets.
- Coverage thresholds require collector configuration in `phpunit.xml`. Initial setup cost.

## References

- ADR-0001 (Vertical Slices) — defines the slice layout that tests mirror.
- `docs/guides/testing.md` — example tests and Foundry/Messenger-Test usage.
- [PHPUnit](https://phpunit.de) · [Symfony Testing](https://symfony.com/doc/current/testing.html) · [Zenstruck Foundry](https://github.com/zenstruck/foundry) · [Zenstruck Messenger-Test](https://github.com/zenstruck/messenger-test).
