<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\View;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method View|null find($id, $lockMode = null, $lockVersion = null)
 * @method View|null findOneBy(array $criteria, array $orderBy = null)
 * @method View[]    findAll()
 * @method View[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, View::class);
    }

    /**
     * @param View $entity
     * @param bool $flush
     */
    public function add(View $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) $this->_em->flush();
    }

    /**
     * @param View $entity
     * @param bool $flush
     */
    public function remove(View $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) $this->_em->flush();
    }

    /** Criteria */
    public static function createUserCriteria(User $user): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('user', $user));
    }
}
