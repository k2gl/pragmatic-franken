---
id: ADR-0013
title: Doctrine Repository Pattern
status: Accepted
date: 2026-06-11
supersedes: []
superseded_by: []
audience: both
summary: "Every entity gets {X}Repository extends DoctrineRepository<X> with typing @method PHPDoc; handlers and validators inject repositories; persist+flush goes through $repo->save(), FK proxies through $repo->reference(). EntityManagerInterface only for rare non-CRUD scenarios."
---

# ADR-0013: Doctrine Repository Pattern

**TL;DR:** Doctrine access goes through typed repositories. Each entity declares
`#[ORM\Entity(repositoryClass: XRepository::class)]`; `XRepository extends
DoctrineRepository<X>` lives in `src/Context/{Name}/Repository/`. CRUD via
`$repo->save()/remove()`, FK proxies via `$repo->reference($id)`, recurring DQL
via named repository methods. `EntityManagerInterface` remains only for rare
non-CRUD scenarios (`clear()`, batch imports).

## Context

The naive `$entityManager->getRepository(X::class)->…` style loses types:
PHPStan sees `find(): ?object`, magic `findOneByEmail` calls are invisible to
static analysis, and `persist()+flush()` pairs scatter across handlers with no
single semantic "save". Proven in the CRM (and the k2gl/time-pick reference)
before being ported back here.

## Decision

### Base class

`src/SharedKernel/Infrastructure/Persistence/DoctrineRepository.php` —
`abstract class` with `@template T`, extending `ServiceEntityRepository<T>`:

- `get($id)` — throws `EntityNotFoundException` when absent (null not allowed);
- `save($entity, bool $flush = true)` / `remove($entity, bool $flush = true)`;
- `reference($id)` — FK proxy without a DB read;
- `flush()` — finalize a batch of `save(..., flush: false)`;
- internal `entityManager()` survives a failed flush (ORM 3 closes the manager
  on commit exceptions; we reset and reacquire so a failed chunk can't block
  status writes in batch jobs).

### Subclass per entity

```php
/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method list<Task> findAll()
 * @method list<Task> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends DoctrineRepository<Task>
 */
final class TaskRepository extends DoctrineRepository
{
    public string $entityClass = Task::class;
}
```

Doctrine Bundle auto-registers subclasses as services — inject them by type
into handlers and validators. Named query methods (`findOverdue()`,
`sumByOwner()`) belong on the repository, not in handlers.

## Consequences

**Positive:** full typing under PHPStan level 10 with zero magic; one semantic
`save()`; repositories are the single home for queries.

**Negative:** one extra class per entity (a 10-line subclass); direct
EntityManager usage needs justification in review.
