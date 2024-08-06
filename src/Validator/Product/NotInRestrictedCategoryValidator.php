<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Repository\Product\CategoryRestrictionsRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotInRestrictedCategoryValidator extends ConstraintValidator
{
    /**
     * @var CategoryRestrictionsRepositoryInterface
     */
    private $repository;

    public function __construct(CategoryRestrictionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotInRestrictedCategory) {
            throw new UnexpectedTypeException($constraint, NotInRestrictedCategory::class);
        }

        if (!is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        if (0 >= $categoryId = (int) ($value['id_category_default'] ?? 0)) {
            return;
        }

        if (!$this->repository->isCategoryRestricted($categoryId, $constraint->shopId)) {
            return;
        }

        $this->context
            ->buildViolation('Product belongs to a restricted category.')
            ->addViolation();
    }
}
