<?php

namespace App\Repository;

use App\Entity\EventMoment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventMoment>
 *
 * @method EventMoment|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventMoment|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventMoment[]    findAll()
 * @method EventMoment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventMomentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventMoment::class);
    }

    public function save(EventMoment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventMoment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 