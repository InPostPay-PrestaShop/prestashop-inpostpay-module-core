<?php

declare(strict_types=1);

namespace izi\prestashop\Security;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Used on PS < 1.7.6 admin config page to prevent Twig cache warmup failing because the "real" user provider service
 * has not yet been set in the container.
 *
 * @internal
 */
final class LazyUserProvider implements UserProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @param string $serviceId name of a service implementing {@see UserProviderInterface}
     */
    public function __construct(ContainerInterface $container, string $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->getUserProvider()->loadUserByUsername($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->getUserProvider()->refreshUser($user);
    }

    public function supportsClass($class): bool
    {
        return $this->userProvider->supportsClass($class);
    }

    private function getUserProvider(): UserProviderInterface
    {
        return $this->userProvider ?? $this->userProvider = $this->container->get($this->serviceId);
    }
}
