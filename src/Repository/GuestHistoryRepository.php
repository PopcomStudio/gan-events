<?php

namespace App\Repository;

use App\Entity\GuestHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GuestHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method GuestHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method GuestHistory[]    findAll()
 * @method GuestHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GuestHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuestHistory::class);
    }

    // /**
    //  * @return GuestHistory[] Returns an array of GuestHistory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GuestHistory
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
