<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;
use izi\prestashop\Configuration\ProductRestrictionsProviderInterface;
use izi\prestashop\Configuration\WidgetDisplayConfigurationInterface;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductPageDisplayConfiguration implements WidgetDisplayConfigurationInterface, ProductRestrictionsProviderInterface
{
    /**
     * @var WidgetDisplayConfiguration
     *
     * @Assert\Valid
     */
    private $displayConfiguration;

    /**
     * @var ProductRestrictions|null
     *
     * @Assert\NotNull
     * @Assert\Valid
     */
    private $productRestrictions;

    public function __construct(WidgetDisplayConfiguration $configuration, ?ProductRestrictions $restrictions = null)
    {
        $this->displayConfiguration = $configuration;
        $this->productRestrictions = $restrictions;
    }

    public function isDisplayed(): bool
    {
        return $this->displayConfiguration->isDisplayed();
    }

    public function getWidgetConfiguration(): WidgetConfigurationInterface
    {
        return $this->displayConfiguration->getWidgetConfiguration();
    }

    public function getHtmlStyles(): iterable
    {
        return $this->displayConfiguration->getHtmlStyles();
    }

    public function getProductRestrictions(): ?ProductRestrictions
    {
        return $this->productRestrictions;
    }

    /**
     * @return $this
     */
    public function setProductRestrictions(?ProductRestrictions $restrictions): self
    {
        $this->productRestrictions = $restrictions;

        return $this;
    }

    public function getDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->displayConfiguration;
    }
}
