<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Repository\Product\FeatureRestrictionsRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotWithRestrictedFeaturesValidator extends ConstraintValidator
{
    /**
     * @var FeatureRestrictionsRepositoryInterface
     */
    private $repository;

    public function __construct(FeatureRestrictionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotWithRestrictedFeatures) {
            throw new UnexpectedTypeException($constraint, NotWithRestrictedFeatures::class);
        }

        if (!\is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        if ([] === $features = ($value['features'] ?? [])) {
            return;
        }

        $featureIds = array_map(static function (array $feature): int {
            return (int) $feature['id_feature'];
        }, $features);

        if (!$this->repository->isAnyFeatureRestricted($featureIds, $constraint->shopId)) {
            return;
        }

        $this->context
            ->buildViolation('Product has restricted features.')
            ->addViolation();
    }
}
