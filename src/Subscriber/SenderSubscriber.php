<?php

namespace App\Subscriber;

use App\Entity\Guest;
use App\Entity\Sender;
use App\Entity\User;
use App\Service\EventHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Security;

class SenderSubscriber implements EventSubscriberInterface
{
    private ?Security $security;
    private EventHelper $eventHelper;

    public function __construct(Security $security, EventHelper $eventHelper)
    {
        $this->security = $security;
        $this->eventHelper = $eventHelper;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof Sender) return;

        $sender = $entity;

        /** @var User $user */
        $user = $this->security->getUser();

        $sender->getEvent()->setCurrentUser($user);

        $this->eventHelper->setEvent($sender->getEvent());
    }
}