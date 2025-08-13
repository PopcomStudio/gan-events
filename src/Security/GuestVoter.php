<?php

namespace App\Security;

use App\Entity\Guest;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GuestVoter extends Voter
{
    const EDIT = 'edit';

    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, [self::EDIT])) return false;

        if (!$subject instanceof Guest) return false;

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) return false;

        /** @var Guest $guest */
        $guest = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($guest, $user);
        }

        throw new \LogicException('An error occured in the Guest Voter.');
    }

    private function canEdit(Guest $guest, User $user): bool
    {
        // User is admin or editor
        $validRoles = ['ROLE_ADMIN', 'ROLE_EDITOR'];
        foreach ($validRoles as $role) {

            if (in_array($role, $user->getRoles())) return true;
        }

        // User is owner of event
        if ($guest->getEvent() === $user) return true;

        // User is sender
        if ($guest->getSender()->getUser() === $user) return true;

        return false;
    }
}