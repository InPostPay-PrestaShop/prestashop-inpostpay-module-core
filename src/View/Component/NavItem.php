<?php

declare(strict_types=1);

namespace izi\prestashop\View\Component;

use Symfony\Component\HttpFoundation\Request;

final class NavItem
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $route;

    /**
     * @var callable(Request): bool
     */
    private $activeChecker;

    /**
     * @param callable(Request): bool|null $activeChecker
     */
    public function __construct(string $id, string $label, string $route, ?callable $activeChecker = null)
    {
        $this->id = $id;
        $this->label = $label;
        $this->route = $route;
        $this->activeChecker = $activeChecker ?? [$this, 'checkRoute'];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function isActive(Request $request): bool
    {
        return ($this->activeChecker)($request);
    }

    private function checkRoute(Request $request): bool
    {
        return $this->route === $request->attributes->get('_route');
    }
}
