---
id: ADR-0010
title: Documentation and AI-Instruction Layout
status: Accepted
date: 2026-04-28
supersedes: []
superseded_by: []
audience: both
summary: "Three-tier doc model: AGENTS.md is the only Tier-1 file always loaded by agents (≤2000 tokens). ADRs and guides are Tier 2 with YAML front-matter for cheap skimming. README and roadmap are Tier 3 (humans only). No per-IDE rule files."
---

# ADR-0010: Documentation and AI-Instruction Layout

**TL;DR:** One agent-default file (`AGENTS.md`). All other docs are lazy-loaded based on YAML front-matter. Per-IDE rule files (`.cursorrules`, `.windsurfrules`, `.cursor/rules/*`) are not maintained — every modern AI tool reads `AGENTS.md` directly or by convention.

## Context

The repository previously held AI instructions in **four** places: root (`AGENTS.md`, `SYSTEM_PROMPT.md`, `.cursorrules`, `.windsurfrules`), `.config/agents/`, `.cursor/rules/*`, and several files under `docs/guides/`. Total surface area: ~7 000 words across 13 files, with ~70 % content overlap and pairs of byte-identical files (`.cursorrules` ≡ `.windsurfrules`). Every cold-start agent read ~30 000 tokens before touching source code.

## Decision

### Three tiers

| Tier | What | Who reads it | Loading |
|---|---|---|---|
| **1** | `/AGENTS.md` (≤ 200 lines, ≤ 2 000 tokens, ≤ 8 KB) | every AI tool, every developer | always loaded |
| **2** | `docs/adr/*.md`, `docs/guides/*.md` | agents on demand, devs on demand | lazy, by `summary` front-matter or `audience` filter |
| **3** | `README.md`, `docs/roadmap.md` | humans | not loaded by agents by default |

### Required front-matter for Tier 2

```yaml
---
id: ADR-NNNN              # for ADRs only
title: Short Title
status: Accepted | Proposed | Superseded
date: YYYY-MM-DD
supersedes: []
superseded_by: []
audience: agent | human | both
summary: "≤300 character one-liner suitable for skimming without loading the body."
---
```

`dev/check-docs.sh` (invoked via `make docs-check`) enforces presence of these fields and the `AGENTS.md` size budget.

### Per-developer overrides

`AGENTS.local.md` (gitignored, copy from `AGENTS.local.md.example`). Local overrides may adjust tone, language, paths, IDE quirks. **Local overrides cannot redefine architectural rules from ADRs.**

### What is not maintained

- **Per-IDE rule files** (`.cursorrules`, `.windsurfrules`, `.cursor/rules/*`, etc.). Every actively-developed AI tool resolves `AGENTS.md` either by direct convention (Cursor 0.45+, Claude Code, OpenAI Codex CLI, Aider, Codeium, Cline) or by being pointed at it. If a contributor's tool refuses, they create a local symlink and gitignore it. We will not duplicate.
- **Per-tool prompt directories** (`prompts/`, `.config/agents/`, `SYSTEM_PROMPT.md`). The contents that are still load-bearing live in `AGENTS.md`; the rest is removed.

### Rename of `scripts/`

Codegen and developer helpers move to `dev/`; deployment scripts move to `ops/`. `bin/` stays for Symfony binaries (`bin/console`).

## Consequences

### Positive

- **Token economy.** Cold-start agent context drops from ~30 000 tokens to ~1 800 tokens (single `AGENTS.md`).
- **Single source of truth.** Every fact about the system has exactly one home.
- **No drift between IDE configs.** There are no IDE configs to drift.
- **Front-matter enables cheap skimming.** Agents can read just front-matter (≈ 200 tokens × 10 ADRs) before fetching any full ADR.

### Negative & mitigations

| Risk | Mitigation |
|---|---|
| Tool that ignores `AGENTS.md` | Contributor creates a local symlink. Not maintained upstream. |
| `AGENTS.md` budget breach over time | `make docs-check` is a CI gate. |
| Loss of past per-IDE content | Recoverable from git history; the deleted content was duplicate. |

## References

- ADR-0001 — Vertical Slices (one of the architectural facts that `AGENTS.md` cites by ID).
- `dev/check-docs.sh` — the enforcement.
