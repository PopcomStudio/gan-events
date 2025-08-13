<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    private Security $security;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    private function getOrCreateQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder('ev');
    }

    private function addId(int $id, QueryBuilder $qb = null): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder($qb)->andWhere('ev.id = :id')->setParameter('id', $id);
    }

    private function joinSenders(QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->leftJoin('ev.senders', 'h')
            ;
    }

    private function joinManagers(QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->leftJoin('ev.managers', 'managers')
            ->addSelect('managers')
            ;
    }

    private function joinOwner(QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->leftJoin('ev.owner', 'u')
            ->addSelect('u')
            ;
    }

    private function joinVisual(QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->leftJoin('ev.visual', 'v')
            ->addSelect('v')
            ;
    }

    private function joinViewers(QueryBuilder $qb = null): QueryBuilder
    {
        return $this
            ->getOrCreateQueryBuilder($qb)
            ->leftJoin('ev.viewers', 'viewers')
            ->addSelect('viewers')
            ;
    }

    public function findAll()
    {
        return $this->getOrCreateQueryBuilder()
            ->orderBy('ev.beginAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findToEdit(int $id)
    {
        $qb = $this->addId($id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /** Find all events that are assigned to user
     *
     * @param User|null $user
     * @param Event|null $event
     * @param bool $archived
     * @return QueryBuilder
     */
    public function getAllQueryBuilder(?User $user = null, ?Event $event = null, bool $archived = false): QueryBuilder
    {
        $qb = $this->joinOwner();
        $this->joinVisual($qb);

        if ($user) {

            $this->joinSenders($qb);

            $qb
                ->andWhere(':user = h.user OR :user MEMBER OF ev.managers OR :user MEMBER OF ev.viewers')
                ->setParameter('user', $user)
            ;
        }

        if ($event) {

            $qb->andWhere('ev.parent = :event')->setParameter('event', $event);

        } else {

            $qb->andWhere('ev.parent IS NULL');
        }

        if (! $archived) {

            $qb
                ->andWhere('ev.archivedAt IS NULL')
                ->orderBy('ev.beginAt', 'ASC')
            ;

        } else {

            $qb
                ->andWhere('ev.archivedAt IS NOT NULL')
                ->orderBy('ev.beginAt', 'DESC')
            ;
        }

        return $qb;
    }

    public function totalTickets(Event $event)
    {
        $qb = $this
            ->createQueryBuilder('e')
            ->select('COUNT(g.id)')
            ->join('e.guests', 'g', Join::WITH, 'g.status IN (\'registered\',\'participated\') AND g.backup=0')
            ->where('e = :event')
            ->setParameter('event', $event)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Récupérer les événements selon leur date de début
     *
     * @param array $deadlines
     * @param \DateTime|null $after2YearsAgo
     * @return array
     */
    public function findByDeadlines(array $deadlines, ?\DateTime $after2YearsAgo): array
    {
        $qb = $this
            ->createQueryBuilder('e')
        ;

        $whereClauses = [];

        foreach ($deadlines as $k => $deadline) {

            $start = $deadline->format('Y-m-d').' 00:00:00';
            $end = $deadline->format('Y-m-d').' 23:59:59';

            $whereClauses[] = 'e.finishAt IS NOT NULL AND '.sprintf(':begin%1$d_1 <= e.finishAt AND e.finishAt < :begin%1$d_2', $k);
            $whereClauses[] = 'e.finishAt IS NULL AND '.sprintf(':begin%1$d_1 <= e.beginAt AND e.beginAt < :begin%1$d_2', $k);

            $qb
                ->setParameter( sprintf('begin%1$d_1', $k), $start )
                ->setParameter( sprintf('begin%1$d_2', $k), $end )
            ;
        }


        if($after2YearsAgo){
            $after2YearsAgo = $after2YearsAgo->format('Y-m-d');
            $after2Years = $after2YearsAgo.' 23:59:59';
            $whereClauses[] = 'e.finishAt IS NOT NULL AND e.finishAt <= :after2years';
            $whereClauses[] = 'e.finishAt IS NULL AND e.beginAt <= :after2years';

            $qb->setParameter('after2years', $after2Years);

        }

        $qb
            ->andWhere(implode(' OR ', $whereClauses))
            ->andWhere('e.closedAt is NULL');

        return $qb->getQuery()->getResult();
    }

















    /** Setter injection */

    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    /** Criteria */

    // Related Events
    public static function createRelatedEventsCriteria(Event $event): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->gt('beginAt', new \DateTime()))
            ->andWhere(Criteria::expr()->neq('id', $event))
            ->andWhere(Criteria::expr()->eq('switchable', true))
            ->orderBy(['beginAt' => 'ASC'])
        ;
    }

    /** Conditions */

    // Limiter les événements aux utilisateurs autorisés
    private function addUserLimitation(QueryBuilder $qb, ?User $user = null): void
    {
        $alias = $qb->getRootAliases()[0];

        // Si l'utilisateur n'est pas renseigné, récupérer l'utilisateur courant
        if ( ! $user ) {

            // Si l'utilisateur courant a les droits sur la gestion des événements,
            // Laisser à null pour ne pas limiter les événements
            $user = $this->security->isGranted('MANAGE_ALL_EVENTS') ? null : $this->security->getUser();
        }

        if ( $user ) {

            $qb
                ->leftJoin($alias.'.senders', 's')
                ->andWhere()
                ->leftJoin($alias.'.children', 'child')
                ->leftJoin('child.senders', 'cs')
                ->andWhere(sprintf('(:user = s.user OR :user MEMBER OF %1$s.managers OR :user MEMBER OF %1$s.viewers) OR (:user = cs.user OR :user MEMBER OF child.managers OR :user MEMBER OF child.viewers)', $alias))
                ->setParameter('user', $user)
            ;
        }
    }

    /** QueryBuilder */

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('e');
    }

    public function getCollections(): array
    {
        $qb = $this->getQueryBuilder();

        $qb
            ->select('e')
            ->andWhere('e.archivedAt IS NULL')
            ->andWhere('e.type = :type')
            ->setParameter('type', 'collection')
            ->orderBy('e.beginAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function getCollectionsArchived(): array
    {
        $qb = $this->getQueryBuilder();
        $currentDate = new \DateTime();
        $deadline = $currentDate->sub(new \DateInterval("P1Y"));

        $qb
            ->select('e')
            ->andWhere('e.archivedAt IS NOT NULL')
            ->andWhere('e.type = :type')
            ->andWhere('e.archivedAt >= :deadline')
            ->setParameter('type', 'collection')
            ->setParameter('deadline', $deadline)
            ->orderBy('e.beginAt', 'ASC');

        return $qb->getQuery()->getResult();
    }


    // Find all events that are assigned to user
    public function getDashboardQueryBuilder(): QueryBuilder
    {
        $qb = $this->getQueryBuilder();
        $alias = $qb->getRootAliases()[0];

        $qb
            ->addSelect('o, v')
            ->leftJoin($alias.'.owner', 'o')
            ->leftJoin($alias.'.visual', 'v')
            ->andWhere($alias.'.parent IS NULL')
            ->andWhere($alias.'.archivedAt IS NULL')
            ->orderBy($alias.'.beginAt', 'ASC')
        ;

        $this->addUserLimitation($qb);

        return $qb;
    }

    // Find all events that are assigned to user
    public function getArchiveQueryBuilder(): QueryBuilder
    {
        $qb = $this->getQueryBuilder();
        $alias = $qb->getRootAliases()[0];

        $qb
            ->addSelect('o, v')
            ->leftJoin($alias.'.owner', 'o')
            ->leftJoin($alias.'.visual', 'v')
            ->andWhere($alias.'.parent IS NULL')
            ->orderBy($alias.'.archivedAt', 'DESC')
        ;

        $this->addUserLimitation($qb);

        return $qb;
    }

    // Find all events in a collection
    public function getCollectionQueryBuilder(Event $event): QueryBuilder
    {
        $qb = $this->getQueryBuilder();
        $alias = $qb->getRootAliases()[0];

        $qb
            ->addSelect('o, v')
            ->leftJoin($alias.'.owner', 'o')
            ->leftJoin($alias.'.visual', 'v')
            ->andWhere($alias.'.parent = :parent')
            ->setParameter('parent', $event)
            ->orderBy($alias.'.beginAt', 'ASC')
        ;

        $this->addUserLimitation($qb);

        return $qb;
    }

    public function findResumeTab(int $id): ?Event
    {
        $qb = $this->getQueryBuilder();
        $alias = $qb->getAllAliases()[0];

        $qb
            ->leftJoin($alias.'.owner', 'o')
            ->addSelect('o')
            ->andWhere($alias.'.id = :id')
            ->setParameter('id', $id)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findSendersTab(int $id): ?Event
    {
        $qb = $this->getQueryBuilder();
        $alias = $qb->getAllAliases()[0];

        $qb
            ->leftJoin($alias.'.senders', 's')
            ->leftJoin('s.user', 'su')
            ->addSelect('s, su')
            ->andWhere($alias.'.id = :id')
            ->setParameter('id', $id)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

}
