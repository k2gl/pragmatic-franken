<?php

declare(strict_types=1);

namespace App\Context\Task\Repository;

use App\Context\Task\Entity\Task;
use App\SharedKernel\Infrastructure\Persistence\DoctrineRepository;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method list<Task> findAll()
 * @method list<Task> findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, $limit = null, $offset = null)
 *
 * @extends DoctrineRepository<Task>
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
}
