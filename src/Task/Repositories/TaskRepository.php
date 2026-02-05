<?php

declare(strict_types=1);

namespace App\Task\Repositories;

use App\Task\Entity\Task;
use App\Board\Entity\Column;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Task> */
final class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByColumn(Column $column): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.column = :column')
            ->setParameter('column', $column)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByAssignee(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.assignee = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTag(string $tag): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('JSONB_CONTAINS(t.metadata, :tag) = true')
            ->setParameter('tag', json_encode(['tags' => [$tag]]))
            ->getQuery()
            ->getResult();
    }

    public function getMaxPosition(int $columnId): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->where('t.column = :colId')
            ->setParameter('colId', $columnId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    public function save(Task $task): void
    {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
    }

    public function delete(Task $task): void
    {
        $this->getEntityManager()->remove($task);
        $this->getEntityManager()->flush();
    }
}
