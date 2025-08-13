<?php

namespace App\Repository;

use App\Entity\EmailSchedule;
use App\Entity\Event;
use App\Entity\Guest;
use App\Entity\Sender;
use App\Entity\Optout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

/**
 * @method Guest|null find($id, $lockMode = null, $lockVersion = null)
 * @method Guest|null findOneBy(array $criteria, array $orderBy = null)
 * @method Guest[]    findAll()
 * @method Guest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GuestRepository extends ServiceEntityRepository
{
    private RequestStack $requestStack;
    private ?LoggerInterface $logger = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guest::class);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param QueryBuilder|null $qb
     * @return QueryBuilder
     */
    private function getOrCreateQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder('g');
    }

    /**
     * @param Event $event
     * @param QueryBuilder|null $qb
     * @return QueryBuilder
     */
    private function addEventQueryBuilder(Event $event, ?QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->andWhere('g.event = :event')
            ->setParameter('event', $event)
        ;
    }

    /**
     * @param Sender $sender
     * @param QueryBuilder|null $qb
     * @return QueryBuilder
     */
    private function addSenderQueryBuilder(Sender $sender, ?QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->andWhere('g.sender = :sender')
            ->setParameter('sender', $sender)
        ;
    }

    public function exists(Guest $guest): bool
    {
        return $this
            ->getOrCreateQueryBuilder()
            ->select('COUNT(g)')
            ->andWhere('g.email = :email')
            ->andWhere('g.sender = :sender')
            ->setParameter('email', $guest->getEmail())
            ->setParameter('sender', $guest->getSender())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findForSchedule(EmailSchedule $schedule)
    {
        $qb = $this
            ->addEventQueryBuilder($schedule->getEvent())
            ->leftJoin(Optout::class, 'opt', Join::WITH, 'g.email = opt.email')
            ->andWhere('opt.email IS NULL')
            ->andWhere('g.backup = 0')
        ;

				if ($schedule->getSender()) {

					$qb = $this->addSenderQueryBuilder($schedule->getSender(), $qb);
				}

				switch ($schedule->getType()):
					case 'invitation':
					case 'up':
						$qb->andWhere('g.status = \'pending\'');
						break;
					case 'reminder':
						$qb->andWhere('g.status = \'registered\'');
						break;
					case 'thanks':
						$qb->andWhere('g.status = \'participated\'');
						break;
				endswitch;

				if ($schedule->getOnlyNew()) {

					$qb
						->leftJoin('g.history', 'h', Join::WITH, 'h.type = :type')
						->andWhere('h.id IS NULL')
						->setParameter('type', $schedule->getType())
						->groupBy('g.id');
				}

        return $qb->getQuery()->getResult();
    }

    public function findSidekicks(Guest $guest): array
    {
        return $this
            ->getOrCreateQueryBuilder()
            ->andWhere('g.parent = :parent')
            ->andWhere('g.type = :type')
            ->setParameter('parent', $guest)
            ->setParameter('type', 'sidekick')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByUuid(string $uuid, Event $event): ?Guest
    {
        $qb = $this
            ->addEventQueryBuilder($event)
            ->andWhere('g.uuid = :uuid')
            ->andWhere('g.backup = 0')
            ->setParameter('uuid', $uuid)
            ->orderBy('g.updatedAt', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByEmail(string $email, Event $event): ?Guest
    {
        $qb = $this->getOriginalOnlyQueryBuilder();

        $this->addEventQueryBuilder($event, $qb)
            ->andWhere('g.email = :email')
            ->setParameter('email', $email)
            ->orderBy('g.updatedAt', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findById(int $id, Event $event): ?Guest
    {
        $qb = $this
            ->addEventQueryBuilder($event)
            ->andWhere('g.id = :id')
            ->setParameter('id', $id)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findList(Sender $sender)
    {
        $qb = $this
            ->addSenderQueryBuilder($sender)
            ->andWhere('g.backup = 0')
            ->addSelect('hInv.sendAt as invitation, COUNT(DISTINCT hInv.id) as invitationTotal')
            ->leftJoin('g.history', 'hInv', Join::WITH, 'hInv.type = \'invitation\'')
            ->addSelect('hUp.sendAt as up, COUNT(DISTINCT hUp.id) as upTotal')
            ->leftJoin('g.history', 'hUp', Join::WITH, 'hUp.type = \'up\'')
            ->addSelect('CASE WHEN opt.email IS NULL THEN 0 ELSE 1 END as optout')
            ->leftJoin(Optout::class, 'opt', Join::WITH, 'opt.email = g.email')
            ->groupBy('g')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findChildren(Guest $guest, $withStatus = null, $withoutStatus = null)
    {
        $qb = $this
            ->createQueryBuilder('g')
            ->where('g.parent = :parent')
            ->setParameter('parent', $guest);

        if (is_array($withStatus)) {

            $qb->andWhere('g.status IN (:withStatus)')
                ->setParameter('withStatus', $withStatus);

        } elseif ($withStatus) {

            $qb->andWhere('g.status = :withStatus')
                ->setParameter('withStatus', $withStatus);
        }

        if (is_array($withoutStatus)) {

            $qb->andWhere('g.status NOT IN (:withoutStatus)')
                ->setParameter('withoutStatus', $withoutStatus);

        } elseif ($withoutStatus) {

            $qb->andWhere('g.status != :withoutStatus')
                ->setParameter('withoutStatus', $withoutStatus);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Event|Sender|null $for
     * @return QueryBuilder
     */
    private function total($for = null): QueryBuilder
    {
        $qb = $this
            ->getOrCreateQueryBuilder()
            ->select('COUNT(g)')
        ;

        if ($for instanceof Event) {

            $qb
                ->where('IDENTITY(g.event) = :eventId')
                ->setParameter('eventId', $for->getId())
            ;

        } elseif ($for instanceof Sender) {

            $qb
                ->where('IDENTITY(g.sender) = :senderId')
                ->setParameter('senderId', $for->getId())
            ;
        }

        return $qb;
    }

    /**
     * @param Event|Sender|null $for
     * @return int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalTickets($for)
    {
        $qb = $this->total($for);

        $qb->andWhere('g.status = \'registered\'')->andWhere('g.backup = 0');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Event|Sender|null $for
     * @return int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalParticipated($for)
    {
        $qb = $this->total($for);

        $qb->andWhere('g.status = \'participated\'')->andWhere('g.backup = 0');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Event|Sender|null $for
     * @return int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalContacts($for)
    {
        $qb = $this->total($for);

        $qb
            ->andWhere('g.backup = 0')
            ->andWhere('g.type = :guest')
            ->setParameter('guest', 'guest')
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Event|Sender|null $for
     * @return int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalSupplies($for)
    {
        $qb = $this->total($for);

        $qb
            ->andWhere('g.status != \'pending\'')
            ->andWhere('g.backup = 0')
            ->andWhere('g.type != :sidekick')
            ->setParameter('sidekick', 'sidekick')
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Event|Sender|null $for
     * @return int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalContacted($for)
    {
        $qb = $this->total($for);

        $qb
            ->select('COUNT(DISTINCT(g.id))')
            ->leftJoin('g.history', 'hist', 'hist.type = \'invitation\'')
            ->andWhere('hist.sendAt IS NOT NULL AND g.backup = 0')
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findFor($entity)
    {
        $qb = $this
            ->createQueryBuilder('g')
            ->andWhere('g.backup = 0')
            ->orderBy('g.lastName', 'ASC')
            ->addOrderBy('g.firstName', 'ASC')
            ->leftJoin('g.moments', 'm')
            ->addSelect('m')
            ->leftJoin('m.event', 'e')
            ->addSelect('e')
        ;

        $withWorkshops = false;

        if ($entity instanceof Event) {
            $withWorkshops = $entity->getType() === 'ateliers';

            $qb
                ->andWhere('IDENTITY(g.event) = :eventId')
                ->setParameter('eventId', $entity->getId())
            ;

        } elseif($entity instanceof Sender) {
            $withWorkshops = $entity->getEvent()->getType() === 'ateliers';

            $qb
                ->andWhere('IDENTITY(g.sender) = :senderId')
                ->setParameter('senderId', $entity->getId())
            ;
        }

        if ($withWorkshops) {
            $qb->leftJoin('g.workshops', 'w')->addSelect('w');
        }

        $query = $qb->getQuery();
        if ($this->logger) {
            $this->logger->debug('SQL Query: ' . $query->getSQL());
            $this->logger->debug('Parameters: ', ['parameters' => $query->getParameters()]);
        }
        
        return $query->getResult();
    }

    /** Setter injection */

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /** Getter */

    public function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /** Query Builder */

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('g');
    }

    private function getOriginalOnlyQueryBuilder(): QueryBuilder
    {
        return $this->getQueryBuilder()->andWhere('g.backup = 0');
    }

    /** Finder */

    public function findOneInSession(Event $event): ?Guest
    {
        $session = $this->getRequest()->getSession();
        $sessionName = sprintf('event_%s', $event->getId());

        if ($id = $session->get($sessionName)) {

            $qb = $this->getOriginalOnlyQueryBuilder();
            $alias = $qb->getRootAliases()[0];

            $qb->andWhere($alias.'.id = :id')->setParameter('id', $id);

            return $qb->getQuery()->getOneOrNullResult();
        }

        return null;
    }
}
