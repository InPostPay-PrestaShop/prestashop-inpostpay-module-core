<?php

declare(strict_types=1);

namespace izi\prestashop\Security\Adapter;

use izi\prestashop\Security\EmployeeAccessCheckerInterface;

final class EmployeeAccessChecker implements EmployeeAccessCheckerInterface
{
    public function isGranted(string $role, int $profileId): bool
    {
        return \Access::isGranted($role, $profileId);
    }
}
