<?php

declare(strict_types=1);

namespace izi\prestashop\Security;

interface EmployeeAccessCheckerInterface
{
    public function isGranted(string $role, int $profileId): bool;
}
