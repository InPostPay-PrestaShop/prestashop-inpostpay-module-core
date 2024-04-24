<?php

declare(strict_types=1);

namespace izi\prestashop\Security;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Simplified implementation for the FO. To be replaced with @see \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
 * when PS migrates FO security to Sf or a need to authenticate user arises.
 *
 * @internal
 */
final class AuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $accessDecisionManager;

    /**
     * @var TokenInterface|null
     */
    private $token;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    public static function create(iterable $voters = []): self
    {
        return new self(new AccessDecisionManager($voters));
    }

    /**
     * @param string|string[] $attributes
     */
    public function isGranted($attributes, $subject = null): bool
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        $token = $this->getToken();

        return $this->accessDecisionManager->decide($token, $attributes, $subject);
    }

    // we don't care about authentication for now
    private function getToken(): TokenInterface
    {
        return $this->token ?? ($this->token = new AnonymousToken('secret', 'anon'));
    }
}
