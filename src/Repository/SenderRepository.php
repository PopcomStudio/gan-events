<?php

namespace App\Repository;

use App\Entity\Sender;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sender|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sender|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sender[]    findAll()
 * @method Sender[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SenderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sender::class);
    }

    /** Criteria */
    public static function createUserCriteria(User $user): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('user', $user));
    }
}
