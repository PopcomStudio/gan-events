<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\EventHelper;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class EventExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var Security */
    private $security;
    /**
     * @var EventHelper
     */
    private $eventHelper;
    /**
     * @var User|null
     */
    private $user;

    public function __construct(Security $security, EventHelper $eventHelper)
    {
        $this->security = $security;
        $this->eventHelper = $eventHelper;
        $this->user = $security->getUser();
    }

    public function getGlobals(): array
    {
        return [
            'eventHelper' => $this->eventHelper
        ];
    }
}