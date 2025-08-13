<?php

namespace App\Repository;

use App\Entity\EmailSchedule;
use App\Entity\Event;
use App\Entity\Sender;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSchedule[]    findAll()
 * @method EmailSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSchedule::class);
    }

    /**
     * @param Event $event
     * @param Sender|null $sender
     * @param null $sended
     * @return int|mixed|string
     */
    public function findCurrSchedules(Event $event, ?Sender $sender = null, $sended = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.event = :event')
            ->setParameter('event', $event)
            ->orderBy('s.sendAt', 'ASC')
        ;

        if ($sender) {

            $qb
                ->andWhere('s.sender = :sender OR s.sender IS NULL')
                ->setParameter('sender', $sender)
            ;
        }

        if ($sended === true) {

            $qb
                ->andWhere('s.sendAt < :now')
                ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function findPrevSchedules(Event $event, ?Sender $sender = null, ?int $limit = null)
    {
        return $this->findPrevNextSchedules($event, $sender, null, 'prev');
    }

    public function findNextSchedules(Event $event, ?Sender $sender = null, ?int $limit = null)
    {
        return $this->findPrevNextSchedules($event, $sender, null, 'next');
    }

    private function findPrevNextSchedules(Event $event, ?Sender $sender = null, ?int $limit = null, ?string $status = null)
    {
        $limit = $limit ?? 3;

        $qb = $this->createQueryBuilder('s')
            ->where('s.event = :event')
            ->setParameter('event', $event)
            ->setMaxResults($limit)
        ;

        if ($sender) {

            $qb
                ->andWhere('s.sender = :sender OR s.sender IS NULL')
                ->setParameter('sender', $sender)
            ;
        }

        if ($status === 'next') {

            $qb
                ->orderBy('s.sendAt', 'ASC')
                ->andWhere('s.sendAt IS NULL')
            ;

        } elseif ($status === 'prev') {

            $qb
                ->orderBy('s.sendAt', 'DESC')
                ->andWhere('s.sendAt IS NOT NULL')
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function findToProcess()
    {
        $qb = $this
            ->createQueryBuilder('s')
            ->innerJoin('s.event', 'e')
            ->andWhere('s.sendAt < :now')
            ->andWhere('e.archivedAt IS NULL')
            ->andWhere('e.closedAt IS NULL')
            ->andWhere('s.processedAt IS NULL')
            ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
            ;

        return $qb->getQuery()->getResult();
    }
}
