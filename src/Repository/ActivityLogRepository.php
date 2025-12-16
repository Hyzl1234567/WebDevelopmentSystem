<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find recent activities with limit
     */
    public function findRecentActivities(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find activity logs with optional filters
     */
    public function findWithFilters(?User $user = null, ?string $action = null, ?\DateTimeImmutable $startDate = null, ?\DateTimeImmutable $endDate = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u');

        if ($user) {
            $qb->andWhere('a.user = :user')
               ->setParameter('user', $user);
        }

        if ($action) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $action);
        }

        if ($startDate) {
            $qb->andWhere('a.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            // Set end date to end of day (23:59:59)
            $endOfDay = $endDate->setTime(23, 59, 59);
            $qb->andWhere('a.createdAt <= :endDate')
               ->setParameter('endDate', $endOfDay);
        }

        return $qb->orderBy('a.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find all activity logs ordered by most recent
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find activity logs by action type
     */
    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find activity logs by entity type
     */
    public function findByEntity(string $entity): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->where('a.entity = :entity')
            ->setParameter('entity', $entity)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find activity logs by user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get unique actions from logs
     */
    public function getUniqueActions(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.action')
            ->where('a.action IS NOT NULL')
            ->orderBy('a.action', 'ASC')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Get unique entities from logs
     */
    public function getUniqueEntities(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.entity')
            ->where('a.entity IS NOT NULL')
            ->orderBy('a.entity', 'ASC')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Count logs by action type
     */
    public function countByAction(string $action): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count logs by user
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}