---
id: ADR-0008
title: Testing Strategy
status: Accepted
date: 2026-04-27
supersedes: []
superseded_by: []
audience: both
summary: "PHPUnit 12 with mirror-of-src test layout; pyramid 60/30/10 (unit/integration/e2e); CI enforces a global 60% statement-coverage floor (dev/check-coverage.php); per-layer targets are recommended fork policy."
---

# ADR-0008: Testing Strategy

**TL;DR:** PHPUnit 12 is the test framework. Tests mirror `src/` one-to-one. Test type is encoded by the base class plus a `#[Group]` attribute, not by a top-level directory. CI enforces one global coverage floor — 60 % of statements (`dev/check-coverage.php`, clover report); the per-layer targets below are recommended fork policy, not a CI gate.

## Context

We considered PHPUnit vs Pest, and three layouts: separated `Unit/Integration/EndToEnd` directories, mirror-of-`src/` with grouping, feature-local tests next to handlers. Criteria: developer productivity, predictability of AI-generated code, CI cost, ease of feature deletion.

## Decision

1. **Framework: PHPUnit 12.** Mature, deeply integrated with Symfony Test framework, abundant AI training data, full PHPStan compatibility. Pest is rejected for this boilerplate (less AI training data, extra plugin layer with Symfony).
2. **Layout: mirror of `src/`** at `tests/Context/{Name}/Features/{Feature}/`; test *type* via base class + `#[Group]` attribute (table below).
3. **Pyramid: 60 / 30 / 10** (unit / integration / e2e). Most logic is exercised in cheap unit tests; integration covers persistence, Messenger, and external adapters; e2e validates HTTP contracts.
4. **Coverage targets** (global 60 % floor enforced in CI; per-layer values are recommended fork policy):

| Layer | Path glob | Minimum line coverage |
|---|---|---|
| Domain | `src/Context/*/Features/*/Domain/`, `src/Context/*/Entity/`, `src/SharedKernel/Domain/` | **90 %** |
| Application | `src/Context/*/Features/*/Application/` | **80 %** |
| Infrastructure | `src/Context/*/Features/*/Infrastructure/`, `src/Context/*/Repository/`, `src/SharedKernel/Infrastructure/` | **60 %** |
| UI / EntryPoint | `src/Context/*/Features/*/EntryPoint/` | **40 %** |

5. **Async support:** `zenstruck/messenger-test` for in-memory bus assertions; `zenstruck/foundry` + `dama/doctrine-test-bundle` for database isolation.

## Test layout

```
tests/
├── bootstrap.php
├── Support/                                  # framework helpers, not tests
│   ├── TestCase/{UnitTestCase, IntegrationTestCase, ApiTestCase}.php
│   ├── Factory/                              # Foundry factories
│   └── Helper/
└── Context/{Name}/Features/{Feature}/{Feature}*Test.php
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
- The global floor needs a coverage driver in CI (pcov) and a clover report; per-layer gating is left to forks that want it.

## References

- ADR-0001 (Vertical Slices) — defines the slice layout that tests mirror.
- `docs/guides/testing.md` — example tests and Foundry/Messenger-Test usage.
- [PHPUnit](https://phpunit.de) · [Symfony Testing](https://symfony.com/doc/current/testing.html) · [Zenstruck Foundry](https://github.com/zenstruck/foundry) · [Zenstruck Messenger-Test](https://github.com/zenstruck/messenger-test).
