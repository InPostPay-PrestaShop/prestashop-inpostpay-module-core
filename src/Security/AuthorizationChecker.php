<?php

declare(strict_types=1);

namespace izi\prestashop\Security;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
final class AuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    /**
     * @var AuthorizationCheckerInterface|null
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager, ?AuthorizationCheckerInterface $authorizationChecker = null, ?TokenStorageInterface $tokenStorage = null)
    {
        $this->accessDecisionManager = $accessDecisionManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param string|string[] $attributes
     */
    public function isGranted($attributes, $subject = null): bool
    {
        if (null !== $this->authorizationChecker && $this->authorizationChecker->isGranted($attributes, $subject)) {
            return true;
        }

        $token = $this->getToken();

        if (!\is_array($attributes)) {
            $attributes = [$attributes];
        }

        return $this->accessDecisionManager->decide($token, $attributes, $subject);
    }

    private function getToken(): TokenInterface
    {
        if (null === $this->tokenStorage) {
            return $this->createNullToken();
        }

        $token = $this->tokenStorage->getToken();

        if (null !== $token && null !== $token->getUser()) {
            return $token;
        }

        return $this->createNullToken();
    }

    private function createNullToken(): TokenInterface
    {
        return class_exists(NullToken::class) ? new NullToken() : new AnonymousToken('secret', 'anon');
    }
}
