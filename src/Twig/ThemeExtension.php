<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    private ?Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('route_name', [$this, 'getRouteName']),
            new TwigFunction('is_route', [$this, 'isRoute']),
        ];
    }

    public function getRouteName(): ?string
    {
        return $this->request->attributes->get('_route');
    }

    public function isRoute(string $route, bool $start = false): bool
    {
        if ($start) return strpos($this->getRouteName(), $route) === 0;

        return $this->getRouteName() === $route;
    }

//    public function isEventRoute(): ?string
//    {
//        return strpos($this->getRouteName(), 'event_') === 0;
//    }
}