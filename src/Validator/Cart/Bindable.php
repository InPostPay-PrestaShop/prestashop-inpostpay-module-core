<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Cart;

use izi\prestashop\Common\BindingPlace;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

final class Bindable extends Constraint
{
    /**
     * @var BindingPlace
     */
    public $bindingPlace;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (!$this->bindingPlace instanceof BindingPlace) {
            throw new InvalidArgumentException(\sprintf('The "bindingPlace" option must be a "%s" case, "%s" given.', BindingPlace::class, get_debug_type($this->bindingPlace)));
        }
    }

    public function getDefaultOption(): string
    {
        return 'bindingPlace';
    }

    public function getRequiredOptions(): array
    {
        return ['bindingPlace'];
    }
}
