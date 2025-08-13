<?php

namespace App\Subscriber;

use App\Entity\Event;
use App\Entity\User;
use App\Service\EventHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class EventSubscriber implements EventSubscriberInterface
{
    private EventHelper $eventHelper;

    public function __construct(EventHelper $eventHelper)
    {
        $this->eventHelper = $eventHelper;
    }

    private function isValid(LifecycleEventArgs $args): bool
    {
        return $args->getObject() instanceof Event;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
        ];
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        // Est-ce que l'entité chargée est bien un événement
        if (! $this->isValid($args) ) return;

        /** @var Event $event */
        $event = $args->getObject();

        $this->eventHelper->setEvent($event);
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->preUpdate($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        if (! $this->isValid($args)) return;

        $event = $args->getObject();

        if ($event->getType() !== Event::TYPE_CINEMA) {

            $event->clearMovie();
        }

        if ($event->getType() !== EVENT::TYPE_WORKSHOPS) {

            $event->clearWorkshops();
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $keyEntity => $entity) {

            if ( ! $entity instanceof Event ) continue;
            if ( is_null($entity->getParent()) ) continue;

            $event = $entity->getParent();

            if ($event->getChildren()->count()) {

                $firstEvent = $event->getFirstChild();
                $event->setBeginAt($firstEvent->getBeginAt());
                $this->persist($em, $uow, $event);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $keyEntity => $entity) {

            if ( ! $entity instanceof Event ) continue;
            if ( is_null($entity->getParent()) ) continue;

            $event = $entity->getParent();

            if ($event->getChildren()->count()) {

                $firstEvent = $event->getFirstChild();
                $event->setBeginAt($firstEvent->getBeginAt());
                $this->persist($em, $uow, $event);
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $keyEntity => $entity) {

            if ( ! $entity instanceof Event ) continue;
            if ( is_null($entity->getParent()) ) continue;

            $event = $entity->getParent();

            if ($event->getChildren()->count()) {

                $firstEvent = $event->getFirstChild($entity);
                $event->setBeginAt($firstEvent ? $firstEvent->getBeginAt() : new \DateTime());
                $this->persist($em, $uow, $event);
            }
        }
    }

    private function persist(EntityManager $em, UnitOfWork $uow, Event $event)
    {
        $em->persist($event);
        $classMetaData = $em->getClassMetadata(Event::class);
        $uow->computeChangeSet($classMetaData, $event);
    }
}