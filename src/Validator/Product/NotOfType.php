<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Product\ProductType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

final class NotOfType extends Constraint
{
    /**
     * @var ProductType|ProductType[]
     */
    public $types = [];

    public function __construct($options = null)
    {
        parent::__construct($options);

        $types = $this->types;
        if (!is_array($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            if (!$type instanceof ProductType) {
                throw new InvalidArgumentException(sprintf('The "types" option must be a list of "%s" cases, "%s" given.', ProductType::class, get_debug_type($type)));
            }
        }

        $this->types = array_unique($types, SORT_REGULAR);
    }

    public function getDefaultOption(): string
    {
        return 'types';
    }

    public function getRequiredOptions(): array
    {
        return ['types'];
    }
}
