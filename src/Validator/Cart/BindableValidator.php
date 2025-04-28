<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Cart;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Validator\Sequentially;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class BindableValidator extends ConstraintValidator
{
    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    public function __construct(\PaymentModule $paymentModule)
    {
        $this->paymentModule = $paymentModule;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Bindable) {
            throw new UnexpectedTypeException($constraint, Bindable::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof \Cart) {
            throw new UnexpectedTypeException($value, \Cart::class);
        }

        $constraints = iterator_to_array($this->getConstraints($constraint->bindingPlace));

        $context = $this->context;
        $validator = $context->getValidator()->inContext($context);

        $validator->validate($value, new Sequentially($constraints));
    }

    private function getConstraints(BindingPlace $bindingPlace): \Generator
    {
        if ($bindingPlace->requiresExistingBasket()) {
            yield new HasProducts();
        }

        yield new PaymentInCurrencyAvailable($this->paymentModule);

        if (BindingPlace::ProductCard() !== $bindingPlace) {
            yield new HasUnrestrictedProduct();
        }
    }
}
