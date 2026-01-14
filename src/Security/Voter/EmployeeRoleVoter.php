<?php

declare(strict_types=1);

namespace izi\prestashop\Security\Voter;

use izi\prestashop\Security\EmployeeAccessCheckerInterface;
use PrestaShopBundle\Entity\Employee\Employee;
use PrestaShopBundle\Security\Admin\Employee as LegacyEmployee;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Checks the roles using the user entity instead of the security token, since the token may not contain
 * all the user roles if it was reloaded from the session.
 */
final class EmployeeRoleVoter implements VoterInterface
{
    /**
     * @var EmployeeAccessCheckerInterface
     */
    private $accessChecker;

    /**
     * @var int
     */
    private $adminProfileId;

    public function __construct(EmployeeAccessCheckerInterface $accessChecker, int $adminProfileId = _PS_ADMIN_PROFILE_)
    {
        $this->accessChecker = $accessChecker;
        $this->adminProfileId = $adminProfileId;
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $user = $token->getUser();

        if (!$user instanceof Employee && !$user instanceof LegacyEmployee) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($user);

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !str_starts_with($attribute, 'ROLE_')) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            if (\in_array($attribute, $roles, true)) {
                return VoterInterface::ACCESS_GRANTED;
            }

            // as of PS 9.0 the module configuration permissions are not assigned to the employee entity
            // so we check them based on the employee profile
            if (!$user instanceof Employee) {
                continue;
            }

            if ($this->adminProfileId === $user->getProfileId()) {
                // superadmins should have all the permissions
                return VoterInterface::ACCESS_GRANTED;
            }

            if (!str_starts_with($attribute, 'ROLE_MOD_MODULE_')) {
                continue;
            }

            if ($this->accessChecker->isGranted($attribute, $user->getProfileId())) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function extractRoles(UserInterface $user): array
    {
        return array_map(static function ($role): string {
            if (\is_string($role)) {
                return $role;
            }

            if (\is_callable([$role, 'getRole'])) {
                return $role->getRole();
            }

            return (string) $role;
        }, $user->getRoles());
    }
}
