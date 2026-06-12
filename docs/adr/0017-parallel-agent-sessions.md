---
id: ADR-0017
title: Parallel Agent Sessions
status: Accepted
date: 2026-06-12
supersedes: []
superseded_by: []
audience: both
summary: "Several AI/dev sessions on one host get isolated stacks via dev/worktree.sh: a git worktree per session, COMPOSE_PROJECT_NAME <prefix>-<slug>, deterministic port slots derived from .env.dist, /ready as the health gate. The shared Claude Code allowlist is committed at .claude/settings.json; machine-local settings stay gitignored."
---

# ADR-0017: Parallel Agent Sessions

**TL;DR:** One repository, many concurrent sessions. `dev/worktree.sh new <slug>` creates an isolated fork: its own git worktree and branch, its own Docker project (`<prefix>-<slug>`), its own port slot computed from the `.env.dist` base scheme, its own `.env` and DB volume. Sessions never contend for containers, ports or schema state. Tool permissions that every session needs are committed once in `.claude/settings.json`.

## Context

Agent-assisted development runs several sessions against one codebase at once. Sharing a single dev stack fails three ways: container name collisions, port collisions, and test-database contention (one session's migrations break another's run). The isolation primitive must be cheap to create and destroy, and safe — a session must not be able to take down the primary stack by accident.

## Decision

### 1. One session = one worktree fork

`dev/worktree.sh new <slug>` creates `/www/wt/<prefix>-<slug>` (git worktree on its own branch), generates a fork `.env`, and starts the stack. `rm`/`prune` tear forks down but refuse to delete uncommitted or unmerged work unless `--force`.

### 2. Deterministic isolation from one source of truth

The base scheme lives in `.env.dist` and only there; the script derives per-fork values for slot *n*:

| Resource | Formula |
|---|---|
| Compose project | `<COMPOSE_PROJECT_NAME>-<slug>` |
| HTTP / HTTPS | base `+ n × 10` |
| DB / Redis / Xdebug | base `+ n` |
| Server name | `<slug>.<base domain>` |

The first free slot is picked by probing actual port usage, so manually started services don't collide. The fork's health gate is `/ready` (ADR-0005) — the same probe CI and deploys use.

### 3. Safety boundary

The script only ever manages Docker projects named `<prefix>-<slug>`. The primary stack (`<prefix>`) is structurally out of reach — there is no code path that stops or removes it.

### 4. Shared agent permissions are repository state

Claude Code reads tool permissions from `.claude/settings.json` (committed, shared — the allowlist of obviously-safe commands: make targets, read-only git/docker, vendor binaries) and `.claude/settings.local.json` (gitignored, machine-local). Committing the shared allowlist means every session — human-supervised or autonomous — starts with the same friction-free baseline instead of re-answering identical permission prompts. This does not conflict with ADR-0010's single-source rule: AGENTS.md remains the only *context* file; `settings.json` carries no instructions, only permissions.

## Consequences

### Positive

- N sessions run truly independently; `make ci` in one fork cannot affect another.
- Forks are disposable: `worktree.sh prune` garbage-collects merged, clean, old ones.
- Port math is predictable — `worktree.sh ls` shows where everything listens.

### Negative

- Each fork costs RAM (own Postgres/Redis/FrankenPHP) — `down-all` frees it.
- Slot math reserves port ranges; heavily occupied hosts may need a different base scheme in `.env.dist`.

## References

- `dev/worktree.sh` — the implementation; `docs/guides/parallel-sessions.md` — the how-to.
- ADR-0005 — `/ready` as the universal health gate.
- ADR-0010 — documentation layout; this ADR owns tool *permissions*, not context.
