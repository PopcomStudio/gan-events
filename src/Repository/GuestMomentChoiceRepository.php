<?php

namespace App\Repository;

use App\Entity\GuestMomentChoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuestMomentChoice>
 *
 * @method GuestMomentChoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method GuestMomentChoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method GuestMomentChoice[]    findAll()
 * @method GuestMomentChoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GuestMomentChoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuestMomentChoice::class);
    }

    public function save(GuestMomentChoice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GuestMomentChoice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 