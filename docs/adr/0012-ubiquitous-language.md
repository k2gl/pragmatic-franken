---
id: ADR-0012
title: Ubiquitous Language & Entity Placement
status: Accepted
date: 2026-06-11
supersedes: []
superseded_by: []
audience: both
summary: "Entities and repositories live at context level (src/Context/{Name}/Entity, Repository), not inside feature slices. Domain terms are full human words — no abbreviations in class, property or route names."
---

# ADR-0012: Ubiquitous Language & Entity Placement

**TL;DR:** A bounded context owns its entities: `src/Context/{Name}/Entity/` and
`Repository/` sit next to `Features/`. Names use full domain words
(`prospectList`, not `pl`; `CompleteTask`, not `CmplTsk`). Ported from the CRM
that grew out of this skeleton, where feature-local entities caused churn the
moment a second feature needed the same aggregate.

## Context

ADR-0001 placed everything inside `Features/{Feature}/Domain/`. Production
experience showed entities are *context*-scoped, not feature-scoped: `Task` is
created by one slice, listed by another, completed by a third. Keeping the
entity inside the first slice forces cross-slice imports that violate slice
isolation, or copy-paste that violates sanity.

## Decision

1. **Entities at context level.** `src/Context/{Name}/Entity/{Aggregate}.php`,
   mapped with attributes, `repositoryClass` pointing into `Repository/`.
2. **Repositories at context level.** `src/Context/{Name}/Repository/{Aggregate}Repository.php`
   extending the SharedKernel `DoctrineRepository` (ADR-0013).
3. **Features stay verbs.** Slices under `Features/` hold use-cases that *act*
   on the context's entities; slice-local `Domain/` keeps only value objects
   and domain events of that use-case.
4. **Full words everywhere.** Class names, properties, route segments, JSON
   fields and table names use complete domain words in the project's language
   (English by default). Abbreviations are allowed only for universally
   understood terms (`id`, `url`, `http`).
5. **One glossary per project.** Forks document their domain glossary in an ADR
   (the CRM's ADR-0012 lists Deal, Prospect, Activity, …) so humans and agents
   share the same vocabulary.

## Consequences

**Positive:** slices can be deleted without touching persistent state; agents
find every aggregate in one predictable place; PHPStan sees typed repositories.

**Negative:** "entity per feature" purists lose a level of isolation — accepted
deliberately (Pragmatism Charter, ADR-0003).
