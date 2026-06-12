---
id: ADR-0009
title: Shared Architecture
status: Accepted
date: 2026-02-06
supersedes: []
superseded_by: []
audience: both
summary: "Two-level Shared: src/SharedKernel/ for cross-context infra glue (repository base, problem+json listeners, heartbeat), src/Context/{Name}/Shared/ for context-internal reuse. Rule of Three: extract only at the third occurrence."
---

# ADR-0009: Shared Architecture

**TL;DR:** `src/SharedKernel/` holds infrastructure glue only; context-internal
reuse goes to `src/Context/{Name}/Shared/`. Don't extract before three
occurrences. ADR-0001 defines slice layout; this ADR defines what lives
*outside* slices. ("Context" is a DDD Bounded Context — see ADR-0001.)

## Context

The `Shared/` directory is the most dangerous place in any architecture.
Without strict rules it becomes a trash bin within 6 months — string helpers
next to business logic that was afraid to pick a context. Vertical slices die
two deaths: a "common" layer that grows into a framework, or copy-paste that
never converges. The cure is a strict definition of *what* may be shared and
*when* extraction is allowed.

## Decision

### Two levels

1. **SharedKernel** (`src/SharedKernel/`) — cross-context infrastructure glue.
   Shipped contents (ported from real production projects grown from this skeleton):

   ```
   src/SharedKernel/
     Domain/Env.php                                       # APP_ENV as a typed enum (k2gl/app-env)
     Infrastructure/Persistence/DoctrineRepository.php    # typed repo base (ADR-0013)
     Infrastructure/Persistence/EntityNotFoundException.php
     Infrastructure/Http/ValidationExceptionListener.php  # 422 problem+json (RFC 9457)
     Infrastructure/Http/SecurityExceptionListener.php    # 403 problem+json
     Infrastructure/Http/NotFoundExceptionListener.php    # 404 problem+json
     Infrastructure/Messenger/WorkerHeartbeatListener.php # worker container healthcheck
     Infrastructure/Cli/SeedCliCommand.php                # app:seed — demo data for dev
   ```

   Allowed here: base abstractions, error envelopes, transport glue.
   Forbidden: anything with domain meaning (no `User`, no `Money` — those
   belong to a context).

2. **Context Shared** (`src/Context/{Name}/Shared/`) — reuse *inside* one
   bounded context: cross-slice events, context-wide value objects, helpers
   used by 3+ slices of that context.

### Rule of Three

Copy-paste twice. Extract at the third occurrence — into Context Shared when
all users are one context, into SharedKernel only when it is domain-free
infrastructure needed by multiple contexts.

### Decision matrix

| Code | Home |
|---|---|
| Used by one slice | inside the slice |
| Used by 2 slices, same context | copy-paste (wait for the third) |
| Used by 3+ slices, same context | `src/Context/{Name}/Shared/` |
| Domain-free infra, multiple contexts | `src/SharedKernel/` |
| Domain concept, multiple contexts | re-examine boundaries — likely a missing context or an event |

## Consequences

**Positive:** slices stay deletable; SharedKernel stays small enough to read in
one sitting; agents get an explicit decision matrix instead of taste.

**Negative:** deliberate duplication before the third occurrence offends DRY
instincts — that is the point (ADR-0003).
