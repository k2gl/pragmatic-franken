<?php

declare(strict_types=1);

namespace App\Context\Task\Repository;

use App\Context\Task\Entity\Task;
use App\SharedKernel\Infrastructure\Persistence\DoctrineRepository;
use DateTimeImmutable;

/**
 * @extends DoctrineRepository<Task>
 *
 * @method Task       get(string $id, ?int $lockMode = null, ?int $lockVersion = null)
 * @method Task       reference(mixed $id)
 * @method Task|null  find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null  findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method list<Task> findAll()
 * @method list<Task> findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, ?int $limit = null, ?int $offset = null)
 */
final class TaskRepository extends DoctrineRepository
{
    public string $entityClass = Task::class;

    /**
     * @return list<Task>
     */
    public function findNewestFirst(int $limit = 100): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    /** Bulk-delete tasks completed before the cutoff; returns the count. */
    public function deleteCompletedBefore(DateTimeImmutable $cutoff): int
    {
        $deleted = $this->createQueryBuilder('task')
            ->delete()
            ->where('task.completedAt IS NOT NULL')
            ->andWhere('task.completedAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
        \assert(\is_int($deleted));

        return $deleted;
    }
}
