<?php

declare(strict_types=1);

namespace App\Board\Repositories;

use App\Board\Entity\Board;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Board> */
final class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    public function findFullBoard(int $boardId, User $user): ?Board
    {
        return $this->createQueryBuilder('b')
            ->select('b', 'c', 't')
            ->leftJoin('b.columns', 'c')
            ->leftJoin('c.tasks', 't')
            ->where('b.id = :id')
            ->andWhere('b.owner = :user')
            ->setParameter('id', $boardId)
            ->setParameter('user', $user)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('t.position', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByOwner(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUuid(string $uuid): ?Board
    {
        return $this->createQueryBuilder('b')
            ->where('b.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Board $board): void
    {
        $this->getEntityManager()->persist($board);
        $this->getEntityManager()->flush();
    }

    public function delete(Board $board): void
    {
        $this->getEntityManager()->remove($board);
        $this->getEntityManager()->flush();
    }
}
