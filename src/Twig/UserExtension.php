<?php

namespace App\Twig;

use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserExtension extends AbstractExtension
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_original_user', [$this, 'isOriginalUser']),
            new TwigFunction('original_user', [$this, 'getOriginalUser']),
        ];
    }

    public function getOriginalUser(): ?UserInterface
    {
        $token = $this->security->getToken();

        if ($token instanceof SwitchUserToken) return $token->getOriginalToken()->getUser();

        return $this->security->getUser();
    }

    public function isOriginalUser(): bool
    {
        $token = $this->security->getToken();

        if ($token instanceof SwitchUserToken) return false;

        return true;
    }
}