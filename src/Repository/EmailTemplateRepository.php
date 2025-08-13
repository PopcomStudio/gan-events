<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use App\Entity\Event;
use App\Entity\Sender;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailTemplate[]    findAll()
 * @method EmailTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * @param Event $event
     * @param Sender|null $sender
     * @return int|mixed|string
     */
    public function findCurrTemplates(Event $event, ?Sender $sender = null)
    {
        $orderBy = [];

        $qb = $this->createQueryBuilder('t')
            ->where('t.event = :event')
            ->setParameter('event', $event)
        ;

        if ($sender) {

            $qb
                ->andWhere('t.sender = :sender OR t.sender IS NULL')
                ->setParameter('sender', $sender)
                ->orderBy('t.sender', 'ASC')
            ;

        } else {

            foreach ($event->getSenders() as $sender) {

                $orderBy[] = $sender->getId();
            }

            $orderBy = '\''.implode('\',\'', $orderBy).'\'';

            $qb->orderBy('FIELD(IDENTITY(t.sender), '.$orderBy.')', 'ASC');
        }

        $orderBy = '\''.implode('\',\'', array_keys(EmailTemplate::getTypes())).'\'';

        $qb->addOrderBy('FIELD(t.type, '.$orderBy.')', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findJson($data)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.id, CONCAT(\'#\', t.id, \' \', t.subject, CASE WHEN t.sender IS NULL THEN \' (commun)\' ELSE \'\' END) as text')
            ->andWhere('t.subject LIKE :subject')
            ->setParameter('subject', '%'.$data['search'].'%')
            ->andWhere('IDENTITY(t.event) = :event')
            ->setParameter('event', $data['event'])
            ->andWhere('t.type = :type')
            ->setParameter('type', $data['type'])
        ;

        if (isset($data['sender']) && !empty($data['sender'])) {

            $qb
                ->andWhere('IDENTITY(t.sender) = :sender OR t.sender IS NULL')
                ->setParameter('sender', intval($data['sender']))
            ;

        }
        else {

            $qb->andWhere('t.sender IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function findForSchedule($data)
    {
        // GoTo : Sécuriser

        $qb = $this->createQueryBuilder('t')
            ->where('t.id = :template')
            ->setParameter('template', $data['template'])
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}
