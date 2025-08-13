<?php

namespace App\Repository;

use App\Entity\Option;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Option|null find($id, $lockMode = null, $lockVersion = null)
 * @method Option|null findOneBy(array $criteria, array $orderBy = null)
 * @method Option[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Option::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('o')->orderBy('o.displayName', 'ASC')->getQuery()->getResult();
    }

    public function findByName(string $name): ?Option
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findLegal(): array
    {
        $options = $this
            ->createQueryBuilder('o', 'o.displayName')
            ->andWhere('o.name IN (\'legal\', \'cgu\', \'privacyPolicy\')')
            ->getQuery()
            ->getResult()
        ;

        foreach ($options as $displayName => $option) {

            if (isset($option->getData()['value'])) $options[$displayName] = $option->getData()['value'];
            else unset($options[$displayName]);
        }

        return $options;
    }

    public function findEmailLegal(): ?string
    {
        $value = $this
            ->createQueryBuilder('o')
            ->select('o.data')
            ->andWhere('o.name = \'emailLegal\'')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $value = $value ? json_decode($value, true) : null;

        return is_array($value) ? $value['value'] : null;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Option $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Option $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Option[] Returns an array of Option objects
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
    public function findOneBySomeField($value): ?Option
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
