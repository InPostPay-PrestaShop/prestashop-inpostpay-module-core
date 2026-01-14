<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Configuration\ProductRestrictionsConfigurationInterface;
use izi\prestashop\Product\Restriction\RestrictedAction;
use izi\prestashop\Validator\Sequentially;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UnrestrictedValidator extends ConstraintValidator
{
    /**
     * @var ProductRestrictionsConfigurationInterface
     */
    private $configuration;

    public function __construct(ProductRestrictionsConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Unrestricted) {
            throw new UnexpectedTypeException($constraint, Unrestricted::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !$value instanceof \ArrayAccess) {
            throw new UnexpectedTypeException($value, 'array|ArrayAccess');
        }

        $action = $this->configuration->getProductRestrictedAction($constraint->shopId);

        if (!$action->appliesTo($constraint->deliveryType, $constraint->strict)) {
            return;
        }

        if ([] === $constraints = $this->configuration->getProductRestrictionConstraints($constraint->shopId)) {
            return;
        }

        $validator = $this->context->getValidator()->startContext();
        $violations = $validator->validate($value, new Sequentially($constraints))->getViolations();

        if (0 === $violations->count()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(RestrictedAction::DisallowOrder() === $action ? Unrestricted::ORDER_DISALLOWED_ERROR : Unrestricted::DELIVERY_DISALLOWED_ERROR)
            ->addViolation();
    }
}
