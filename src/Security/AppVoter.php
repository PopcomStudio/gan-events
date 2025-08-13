<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class AppVoter extends Voter
{
    const MANAGE_USERS = 'MANAGE_USERS';
    const CREATE_EVENT = 'CREATE_EVENT';
    const MANAGE_ALL_EVENTS = 'MANAGE_ALL_EVENTS';
    const EDIT_USER = 'EDIT_USER';

    private ?User $user;
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    private function getAttributes(): array
    {
        return [
            self::MANAGE_USERS,
            self::CREATE_EVENT,
            self::MANAGE_ALL_EVENTS,
            self::EDIT_USER,
        ];
    }

    protected function supports($attribute, $subject): bool
    {
        if ( ! in_array($attribute, $this->getAttributes()) ) return false;

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $this->user = $token->getUser();

        if (!$this->user instanceof User) return false;

        switch ($attribute) {
            case self::MANAGE_USERS:
                return $this->canManageUsers();
            case self::CREATE_EVENT:
                return $this->canCreateEvent();
            case self::MANAGE_ALL_EVENTS:
                return $this->canManageAllEvents();
            case self::EDIT_USER:
                return $this->canEditUser($subject);
        }

        throw new \LogicException('An error occured in the Voter.');
    }

    private function canManageUsers(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canCreateEvent(): bool
    {
         return
             $this->security->isGranted('ROLE_ADMIN') ||
             $this->security->isGranted('ROLE_EDITOR') ||
             $this->security->isGranted('ROLE_AUTHOR')
         ;
    }

    private function canManageAllEvents(): bool
    {
        return
            $this->security->isGranted('ROLE_ADMIN') ||
            $this->security->isGranted('ROLE_EDITOR')
            ;
    }

    private function canEditUser(User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) return true;

        return $user === $this->security->getUser();
    }

}