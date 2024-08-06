<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Repository\Product\ManufacturerRestrictionsRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotFromRestrictedManufacturerValidator extends ConstraintValidator
{
    /**
     * @var ManufacturerRestrictionsRepositoryInterface
     */
    private $repository;

    public function __construct(ManufacturerRestrictionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotFromRestrictedManufacturer) {
            throw new UnexpectedTypeException($constraint, NotFromRestrictedManufacturer::class);
        }

        if (!is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        if (0 >= $manufacturerId = (int) ($value['id_manufacturer'] ?? 0)) {
            return;
        }

        if (!$this->repository->isManufacturerRestricted($manufacturerId, $constraint->shopId)) {
            return;
        }

        $this->context
            ->buildViolation('Product comes from a restricted manufacturer.')
            ->addViolation();
    }
}
