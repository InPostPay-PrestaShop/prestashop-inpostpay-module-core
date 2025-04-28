<?php

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Configuration\ProductRestrictionsConfigurationInterface;
use izi\prestashop\Validator\Sequentially;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UnrestrictedValidator extends ConstraintValidator
{
    /**
     * @var ProductRestrictionsConfigurationInterface
     */
    private $productRestrictionConstraints;

    /**
     * @var array<int, Constraint[]> constraints by shop ID
     */
    private $constraints;

    public function __construct(ProductRestrictionsConfigurationInterface $productRestrictionConstraints)
    {
        $this->productRestrictionConstraints = $productRestrictionConstraints;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Unrestricted) {
            throw new UnexpectedTypeException($constraint, Unrestricted::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        $constraints = $this->getConstraints($constraint->shopId);
        if (empty($constraints)) {
            return;
        }

        $validator = $this->context->getValidator()->inContext($this->context);
        $validator->validate($value, new Sequentially($constraints));
    }

    /**
     * @return Constraint[]
     */
    private function getConstraints(?int $shopId): array
    {
        $key = (int) $shopId;

        if (!isset($this->constraints[$key])) {
            $this->constraints[$key] = $this->productRestrictionConstraints->getProductRestrictionConstraints($shopId);
        }

        return $this->constraints[$key];
    }
}
