<?php

namespace App\Security;

use App\Entity\Event;
use App\Entity\User;
use App\Service\EventHelper;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class EventVoter extends Voter
{
    public const DASHBOARD   = 'DASHBOARD';
    public const SWITCH_USER = 'SWITCH_USER';
    public const EDIT   = 'EDIT';
    public const MANAGE_CONTACTS = 'CONTACTS';
    public const MANAGE = 'MANAGE';
    public const MANAGE_EMAIL = 'MANAGE_EMAIL';
    public const MANAGE_EMAIL_SCHEDULES = 'MANAGE_EMAIL_SCHEDULES';
    public const MANAGE_EMAIL_TEMPLATES = 'MANAGE_EMAIL_TEMPLATES';
    public const MANAGE_EMAIL_VISUALS = 'MANAGE_EMAIL_VISUALS';
    public const MANAGE_QRCODE = 'MANAGE_QRCODE';
    public const MANAGE_SENDERS = 'MANAGE_SENDERS';

    private EventHelper $eventHelper;
    private User $user;
    private Event $event;
    private Security $security;

    public function __construct(EventHelper $eventHelper, Security $security)
    {
        $this->eventHelper = $eventHelper;
        $this->security = $security;
    }

    private function getAttributes(): array
    {
        return [
            self::DASHBOARD,
            self::SWITCH_USER,
            self::EDIT,
            self::MANAGE_CONTACTS,
            self::MANAGE,
            self::MANAGE_EMAIL,
            self::MANAGE_EMAIL_SCHEDULES,
            self::MANAGE_EMAIL_TEMPLATES,
            self::MANAGE_EMAIL_VISUALS,
            self::MANAGE_QRCODE,
            self::MANAGE_SENDERS,
        ];
    }

    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, $this->getAttributes())) return false;

        if (!$subject instanceof Event) return false;

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if ( ! $user instanceof User ) return false;

        $this->user = $user;
        $this->event = $subject;

        switch ($attribute) {
            case self::DASHBOARD:
                return $this->canViewDashboard();
            case self::EDIT:
            case self::MANAGE:
                return $this->canEdit();
            case self::MANAGE_CONTACTS:
                return $this->canViewContacts();
            case self::SWITCH_USER:
                return $this->canSwitchUser();
            case self::MANAGE_EMAIL:
                return $this->canManageEmail();
            case self::MANAGE_EMAIL_SCHEDULES:
                return $this->canManageEmailSchedules();
            case self::MANAGE_EMAIL_TEMPLATES:
                return $this->canManageEmailTemplates();
            case self::MANAGE_EMAIL_VISUALS:
                return $this->canManageEmailVisuals();
            case self::MANAGE_QRCODE:
                return $this->canManageQrCode();
            case self::MANAGE_SENDERS:
                return $this->canManageSenders();
        }

        throw new \LogicException('An error occured in the Event Voter.');
    }

    private function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_EDITOR');
    }

    private function isOwner(): bool
    {
        return $this->event->getOwner() === $this->user;
    }

    private function isManager(): bool
    {
        return $this->event->getManagers()->contains($this->user);
    }

    private function isViewer(): bool
    {
        return $this->event->getViewers()->contains($this->user);
    }

    private function canViewDashboard(): bool
    {
        if ($this->isAdmin()) return true;

        if ($this->isOwner() || $this->isManager() || $this->isViewer()) return true;

        if ($this->eventHelper->getCurrentSender()) return true;

        return false;
    }

    private function canViewContacts(): bool
    {
        if ($this->eventHelper->getCurrentSender()) return true;

        return false;
    }

    private function canSwitchUser(): bool
    {
        if ($this->isAdmin()) return true;

        if ($this->isOwner() || $this->isManager() || $this->isViewer()) return true;

        $senders = $this->event->getSenders()->matching((new Criteria())->andWhere(Criteria::expr()->eq('user', $this->user)));

        if ($senders->count() > 1) return true;

        return false;
    }

    private function canEdit(): bool
    {
        // User is owner
        if ($this->event->getOwner() === $this->user) return true;

        // User is admin or editor
        $validRoles = ['ROLE_ADMIN', 'ROLE_EDITOR'];
        foreach ($validRoles as $role) {

            if (in_array($role, $this->user->getRoles())) return true;
        }

        if ($this->event->getManagers()->contains($this->user)) return true;

        return false;
    }

    private function canManageEmail(): bool
    {
        if ($this->eventHelper->getCurrentSender() === null) {

            if ($this->canEdit()) return true;

        } elseif (
            $this->eventHelper->getCurrentSender()->getAutonomyOnEmails() ||
            $this->eventHelper->getCurrentSender()->getAutonomyOnSchedule()
        ) {

            return true;
        }

        return false;
    }

    private function canManageEmailSchedules(): bool
    {
        // Si on n'est sur la vue gestionnaire, l'utilisateur doit avoir les droits de gestionnaire
        if ( $this->eventHelper->getCurrentSender() === null ) return $this->canEdit();

        // Sinon l'utilisateur doit avoir le droit de planifier ses emails
        return $this->eventHelper->getCurrentSender()->getAutonomyOnSchedule();
    }

    private function canManageEmailTemplates(): bool
    {
        // Si on n'est sur la vue gestionnaire, l'utilisateur doit avoir les droits de gestionnaire
        if ( $this->eventHelper->getCurrentSender() === null ) return $this->canEdit();

        // Sinon l'utilisateur doit avoir le droit de configurer ses emails
        return $this->eventHelper->getCurrentSender()->getAutonomyOnEmails();
    }

    private function canManageEmailVisuals(): bool
    {
        // L'utilisateur doit être sur la vue gestionnaire et doit être gestionnaire
        return ! $this->eventHelper->getCurrentSender() && $this->canEdit();
    }

    private function canManageQrCode(): bool
    {
        // User is owner
        if ($this->event->getOwner() === $this->user) return true;

        // User is admin or editor
        $validRoles = ['ROLE_ADMIN', 'ROLE_EDITOR'];
        foreach ($validRoles as $role) {

            if (in_array($role, $this->user->getRoles())) return true;
        }

        if ($this->event->getManagers()->contains($this->user)) return true;

        return false;
    }

    private function canManageSenders(): bool
    {
        if ( $this->eventHelper->getCurrentSender() ) return false;

        return $this->canEdit();
    }
}