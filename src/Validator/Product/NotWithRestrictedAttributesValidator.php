<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Repository\Product\AttributeRestrictionsRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotWithRestrictedAttributesValidator extends ConstraintValidator
{
    /**
     * @var AttributeRestrictionsRepositoryInterface
     */
    private $repository;

    public function __construct(AttributeRestrictionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotWithRestrictedAttributes) {
            throw new UnexpectedTypeException($constraint, NotWithRestrictedAttributes::class);
        }

        if (!is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        if ([] === $attributes = ($value['attributes'] ?? [])) {
            return;
        }

        $attributeGroupIds = array_keys($attributes);

        if (!$this->repository->isAnyAttributeGroupRestricted($attributeGroupIds, $constraint->shopId)) {
            return;
        }

        $this->context
            ->buildViolation('Product has attributes from restricted groups.')
            ->addViolation();
    }
}
