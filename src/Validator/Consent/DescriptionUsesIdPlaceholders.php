<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Consent;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS", "ANNOTATION"})
 */
final class DescriptionUsesIdPlaceholders extends Constraint
{
    public function getTargets(): array
    {
        return [
            Constraint::CLASS_CONSTRAINT,
            Constraint::PROPERTY_CONSTRAINT,
        ];
    }
}
