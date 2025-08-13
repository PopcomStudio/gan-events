<?php

namespace App\Subscriber;

use App\Entity\EmailTemplate;
use App\Service\EventHelper;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class EmailTemplateSubscriber implements EventSubscriberInterface
{
    private EventHelper $eventHelper;

    public function __construct(EventHelper $eventHelper)
    {
        $this->eventHelper = $eventHelper;
    }

    private function isValid(LifecycleEventArgs $args): bool
    {
        return $args->getObject() instanceof EmailTemplate;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        if (! $this->isValid($args)) return;

        /** @var EmailTemplate $template */
        $template = $args->getObject();

        $this->eventHelper->setEvent($template->getEvent());
    }
}