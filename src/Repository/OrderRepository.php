<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Fetch all orders with their related customer and product using LEFT JOINs.
     * This prevents Doctrine from trying to proxy/load missing related entities during template rendering.
     *
     * @return Order[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->leftJoin('o.product', 'p')
            ->addSelect('p')
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
