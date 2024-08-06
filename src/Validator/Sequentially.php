<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use Symfony\Component\Validator\Constraints\Composite;

/**
 * Use this constraint to sequentially validate nested constraints.
 * Validation for the nested constraints collection will stop at first violation.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class Sequentially extends Composite
{
    public $constraints = [];

    public function __construct($constraints = null)
    {
        parent::__construct($constraints ?? []);
    }

    public function getDefaultOption(): ?string
    {
        return 'constraints';
    }

    public function getRequiredOptions(): array
    {
        return ['constraints'];
    }

    protected function getCompositeOption(): string
    {
        return 'constraints';
    }

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
