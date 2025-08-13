<?php

namespace App\Repository;

use App\Entity\Optout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Optout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Optout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Optout[]    findAll()
 * @method Optout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OptoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Optout::class);
    }

    // /**
    //  * @return Optout[] Returns an array of Optout objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Optout
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
