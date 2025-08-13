<?php

namespace App\Subscriber;

use App\Entity\User;
use App\Service\Mailer;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSubscriber implements EventSubscriberInterface
{
    private Mailer $mailer;
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @param Mailer $mailer
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(Mailer $mailer, UserPasswordHasherInterface $passwordHasher)
    {
        $this->mailer = $mailer;
        $this->passwordHasher = $passwordHasher;
    }

    private function isValid(LifecycleEventArgs $args): bool
    {
        return $args->getObject() instanceof User;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        if (! $this->isValid($args)) return;

        /** @var User $user */
        $user = $args->getObject();

        $plainPassword = $user->getPlainPassword() ?: $user::generatePassword();

        $user->setPassword( $this->passwordHasher->hashPassword($user, $plainPassword) );
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        if (! $this->isValid($args)) return;

        /** @var User $user */
        $user = $args->getObject();

        if ($user->getEventRegistration()) {

            $this->mailer->sendNewSenderMessage($user, $user->getEventRegistration());

        } else {

            $this->mailer->sendNewUserMessage($user);
        }
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        if ( ! $this->isValid($args)) return;

        /** @var User $user */
        $user = $args->getObject();

        if ($user->getPlainPassword()) {

            $user->setPassword( $this->passwordHasher->hashPassword($user, $user->getPlainPassword()) );
        }
    }
}