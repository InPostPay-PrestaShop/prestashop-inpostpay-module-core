<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Validator\Sequentially;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProductPageDisplayConfiguration implements ProductAwareWidgetDisplayConfigurationInterface
{
    /**
     * @var WidgetDisplayConfigurationInterface
     */
    private $configuration;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Constraint[]
     */
    private $constraints;

    /**
     * @param Constraint[] $constraints constraints the product must be validated against to decide if widget should be displayed
     */
    public function __construct(WidgetDisplayConfigurationInterface $configuration, ValidatorInterface $validator, array $constraints)
    {
        if (BindingPlace::ProductCard() !== $bindingPlace = $configuration->getWidgetConfiguration()->getBindingPlace()) {
            throw new \DomainException('Expected binding place to be "%s", "%s" given', BindingPlace::ProductCard()->value, $bindingPlace->value);
        }

        $this->configuration = $configuration;
        $this->validator = $validator;
        $this->constraints = $constraints;
    }

    public function isDisplayed($product = null): bool
    {
        if (null === $product) {
            return $this->configuration->isDisplayed();
        }

        if (!$this->configuration->isDisplayed($product)) {
            return false;
        }

        if ([] === $this->constraints) {
            return true;
        }

        $violations = $this->validator->validate($product, new Sequentially([
            'constraints' => $this->constraints,
        ]));

        return 0 === count($violations);
    }

    public function getWidgetConfiguration(): WidgetConfigurationInterface
    {
        return $this->configuration->getWidgetConfiguration();
    }

    public function getHtmlStyles(): iterable
    {
        return $this->configuration->getHtmlStyles();
    }
}
