<?php

declare(strict_types=1);

namespace InPost\Izi\PHPStan\Rules;

use izi\prestashop\Enum\Enum;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;

final class EnumExtension implements AlwaysUsedClassConstantsExtension
{
    public function isAlwaysUsed(ConstantReflection $constant): bool
    {
        return $constant->getDeclaringClass()->is(Enum::class);
    }
}
