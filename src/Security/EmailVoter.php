<?php

namespace App\Security;

use App\Entity\EmailSchedule;
use App\Entity\EmailTemplate;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class EmailVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const SEND = 'send';

    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::SEND, self::EDIT])) return false;

        if (!$subject instanceof EmailTemplate && !$subject instanceof EmailSchedule) return false;

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) return false;

        /** @var EmailTemplate|EmailSchedule $email */
        $email = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($email, $user);
            case self::SEND:
                return $this->canSend($email, $user);
//            case self::ADD_GLOBAL:
//                return $this->canAddGlobal($email, $user);
            case self::EDIT:
                return $this->canEdit($email, $user);
        }

        throw new \LogicException('An error occured in the EmailTemplate Voter.');
    }

    private function canView($email, User $user): bool
    {
        // User can edit
        if ($this->canEdit($email, $user)) return true;

        return false;
    }

    private function canSend($email, User $user): bool
    {
        return $this->can($email, $user);
    }

    private function canEdit($email, User $user): bool
    {
        if ($email instanceof EmailSchedule && $email->getProcessedAt()) return false;

        return $this->can($email, $user);
    }

    private function can($email, User $user): bool
    {
        if ($email instanceof EmailSchedule) return true; // GoTo : n'est pas sécurisé

        // User is owner
        if ($email->getSender() && $email->getSender()->getUser() === $user) {

            if ($email instanceof EmailSchedule && $email->getSender()->getAutonomyOnSchedule()) return true;
            elseif ($email instanceof EmailTemplate) return true;
        }

        // User is editor
        return $this->security->isGranted('edit', $email->getEvent());
    }
}