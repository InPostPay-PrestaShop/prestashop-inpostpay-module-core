<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\CombinationRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
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
    /**
     * @var CombinationRepository
     */
    private $combinationRepository;

    /**
     * @param CombinationRepository|null $combinationRepository
     */
    public function __construct(AttributeRestrictionsRepositoryInterface $repository, ?ObjectRepositoryInterface $combinationRepository = null)
    {
        $this->repository = $repository;
        $this->combinationRepository = $combinationRepository ?? self::createCombinationRepository();
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

        if (!is_array($attributes)) {
            $attributeGroupIds = $this->combinationRepository->getAttributeGroupIds((int) $value['id_product'], (int) $value['id_product_attribute']);
        } else {
            $attributeGroupIds = array_keys($attributes);
        }

        if (!$this->repository->isAnyAttributeGroupRestricted($attributeGroupIds, $constraint->shopId)) {
            return;
        }

        $this->context
            ->buildViolation('Product has attributes from restricted groups.')
            ->addViolation();
    }

    private static function createCombinationRepository(): ObjectRepositoryInterface
    {
        /** @var \InPostIzi $module */
        $module = \Module::getInstanceByName('inpostizi');

        return $module->get(ObjectManagerInterface::class)->getRepository(\Combination::class);
    }
}
