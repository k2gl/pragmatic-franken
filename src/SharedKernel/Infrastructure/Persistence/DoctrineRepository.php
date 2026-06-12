<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Persistence;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Project-wide repository base (ADR-0013): each subclass declares $entityClass,
 * gets typed find/findBy/findOneBy/findAll via PHPDoc @method, and the domain
 * operations get/save/remove/count. Doctrine Bundle auto-registers subclasses
 * as services (doctrine.repository_service tag), so handlers and validators
 * inject them by type.
 *
 * @template T of object
 *
 * @template-extends ServiceEntityRepository<T>
 */
abstract class DoctrineRepository extends ServiceEntityRepository
{
    /** @var class-string<T> */
    public string $entityClass;

    public function __construct(private readonly ManagerRegistry $doctrine)
    {
        parent::__construct($doctrine, $this->entityClass);
    }

    /**
     * Fetch an entity by id; throws {@see EntityNotFoundException} when absent.
     * Use when null is not acceptable by business invariant.
     *
     * @return T
     *
     * @throws EntityNotFoundException
     */
    public function get(string $id, ?int $lockMode = null, ?int $lockVersion = null): object
    {
        return $this->entityManager()->find($this->entityClass, $id, $lockMode, $lockVersion)
            ?? throw new EntityNotFoundException(sprintf('%s with id "%s" is not found', $this->entityClass, $id));
    }

    /**
     * Lazy proxy reference without loading from the DB — for setting FK
     * relations and writing without reading. Does not throw on a missing id:
     * Doctrine materializes (or fails) lazily.
     *
     * @return T
     */
    public function reference(mixed $id): object
    {
        $reference = $this->entityManager()->getReference($this->entityClass, $id);
        \assert($reference !== null);

        return $reference;
    }

    /**
     * Persist an entity; flushes immediately by default. For batched writes
     * pass $flush=false and call flush() once at the end.
     *
     * @param T $entity
     */
    public function save(object $entity, bool $flush = true): void
    {
        $entityManager = $this->entityManager();
        $entityManager->persist($entity);

        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * @param T $entity
     */
    public function remove(object $entity, bool $flush = true): void
    {
        $entityManager = $this->entityManager();
        $entityManager->remove($entity);

        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function count(array $criteria = []): int
    {
        return parent::count($criteria);
    }

    /** Finalize a batch of save(..., flush: false) calls in one commit. */
    public function flush(): void
    {
        $this->entityManager()->flush();
    }

    /**
     * EntityManager that survives a failed flush: Doctrine ORM 3 closes the
     * manager when commit throws, and a closed manager can no longer write.
     * Batched imports write in chunks, so one failed chunk must not prevent
     * the task from recording its status — when closed, reset and reacquire.
     */
    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = $this->doctrine->getManagerForClass($this->entityClass);

        if (
            ! $entityManager instanceof EntityManagerInterface
            || ! $entityManager->isOpen()
        ) {
            $this->doctrine->resetManager();
            $entityManager = $this->doctrine->getManagerForClass($this->entityClass);
        }
        \assert($entityManager instanceof EntityManagerInterface);

        return $entityManager;
    }
}
