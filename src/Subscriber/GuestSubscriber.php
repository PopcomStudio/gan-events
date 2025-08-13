<?php

namespace App\Subscriber;

use App\Entity\Guest;
use App\Entity\User;
use App\Service\EventHelper;
use App\Service\Mailer;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Security;

class GuestSubscriber implements EventSubscriberInterface
{
    private ?Security $security;
    private EventHelper $eventHelper;
    private Mailer $mailer;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        Security $security,
        EventHelper $eventHelper,
        Mailer $mailer,
        PropertyAccessorInterface $propertyAccessor
    )
    {
        $this->security = $security;
        $this->eventHelper = $eventHelper;
        $this->mailer = $mailer;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
            Events::prePersist,
            Events::onFlush,
        ];
    }

    private function isValidEntity($args): bool
    {
        return $args->getObject() instanceof Guest;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        if (! $this->isValidEntity($args)) return;

        /** @var Guest $guest */
        $guest = $args->getObject();

        // If it's not a backup => Set UUID
        if ( ! $guest->getUuid() ) {

            $guest->setUuid(Uuid::uuid5(
                Guest::NAMESPACE,
                $guest->getEmail().'-'.$guest->getSender()->getId()
            ));
        }

        if ($guest->isProspect()) {

            // ToDo : Envoyer un mail à la personne !
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $classMetaData = $em->getClassMetadata(Guest::class);

        foreach ($uow->getScheduledEntityUpdates() as $entity) {

            if ( ! $entity instanceof Guest ) continue;

            $guest = $entity;
            $changeSet = $uow->getEntityChangeSet($guest);

            //> Envoyer l'email de confirmation si c'est pour une inscription

            if ($guest->getSendEmail() === Guest::STATUS_REGISTERED) {
                $this->mailer->sendConfirmationMessage($guest);
            }

            //> Le statut change, on fait un backup

            if ( array_key_exists('status', $changeSet) ) {

                $backup = clone $guest;

                foreach ($changeSet as $property => $values) {

                    $this->propertyAccessor->setValue($backup, $property, $values[0]);
                }

                $backup->setBackup(1)->setParent($guest);
                $em->persist($backup);
                $uow->computeChangeSet($classMetaData, $backup);

                $guest->setUpdatedAt(new \DateTime());
            }

            //> L'expéditeur change

            elseif ( array_key_exists('sender', $changeSet) && $guest->getEvent()->isSwitchable() ) {

                // Récupérer l'original (si il existe)
                $original = $guest->getParent();

                // Si l'original existe, on édite l'invité sans dupliquer
                if ( $original ) {

                    // Si le nouvel expéditeur est identique à l'original
                    if ( $original->getSender() === $guest->getSender() ) {

                        // Laisser les valeurs originales à l'invité
                        // Le noter "backup"
                        $guest
                            ->setBackup()
                            ->setEvent($changeSet['event'][0])
                            ->setSender($changeSet['sender'][0])
                        ;

                        // Changer le statut de l'original
                        $original->setStatus('pending');

                        $em->persist($original);
                        $uow->computeChangeSet($classMetaData, $original);
                    }

                    continue;
                }

                //> Préparation du clone
                $clone = clone $guest;

                // Appliquer les nouvelles valeurs au clone
                foreach ($changeSet as $property => $values) {

                    $this->propertyAccessor->setValue($clone, $property, $values[1]);
                }

                // Ajouter l'original en tant que parent du clone
                $clone
                    ->setParent($guest)
                    ->setUpdatedAt(new \DateTime())
                ;

                //> Préparation de l'original
                // Appliquer les anciennes valeurs à l'original
                foreach ($changeSet as $property => $values) {

                    $this->propertyAccessor->setValue($guest, $property, $values[0]);
                }

                // Modifier le statut sur "switched"
                $guest->setStatus(Guest::STATUS_SWITCHED);

                //> Sauvegarder le clone
                $em->persist($clone);
                $uow->computeChangeSet($classMetaData, $clone);

            } else {

                $guest->setUpdatedAt(new \DateTime());
            }
        }
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        if (! $this->isValidEntity($args)) return;

        $guest = $args->getObject();

        /** @var User $user */
        $user = $this->security->getUser();

        $guest->getEvent()->setCurrentUser($user);

        $this->eventHelper->setEvent($guest->getEvent());
    }
}