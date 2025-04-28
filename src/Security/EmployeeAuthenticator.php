<?php

declare(strict_types=1);

namespace izi\prestashop\Security;

use PrestaShopBundle\Security\Admin\Employee;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * A dummy authenticator used on PS < 1.7.6 admin config page.
 *
 * @internal
 */
final class EmployeeAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Context $context)
    {
        $this->context = $context;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return $this->redirectToLoginPage();
    }

    public function getCredentials(Request $request): ?string
    {
        $cookieName = $this->context->cookie->getName();

        if (!$request->cookies->has($cookieName)) {
            return null;
        }

        return $this->context->cookie->email;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if (null === $credentials) {
            return null;
        }

        return $userProvider->loadUserByUsername($credentials);
    }

    /**
     * @param Employee $user
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        /** @var \Employee $employee */
        $employee = $user->getData();

        return (bool) $employee->isLoggedBack();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        return $this->redirectToLoginPage();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function redirectToLoginPage(): RedirectResponse
    {
        $url = $this->context->link->getAdminLink('AdminLogin');

        return new RedirectResponse($url);
    }
}
