<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\WidgetDisplayConfigurationInterface;
use izi\prestashop\View\Widget\Size;
use izi\prestashop\View\Widget\WidgetConfiguration;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class WidgetDisplayConfiguration implements WidgetDisplayConfigurationInterface
{
    /**
     * @var BindingPlace
     */
    private $bindingPlace;

    /**
     * @var bool|null
     *
     * @Assert\NotNull
     */
    private $displayed;

    /**
     * @var WidgetConfigurationInterface|null
     *
     * @Assert\Valid
     */
    private $widgetConfiguration;

    /**
     * @var HtmlStyles|null
     *
     * @Assert\Valid
     */
    private $htmlStyles;

    public function __construct(WidgetConfigurationInterface $widgetConfiguration, bool $displayed = false, ?HtmlStyles $htmlStyles = null)
    {
        $this->bindingPlace = $widgetConfiguration->getBindingPlace();
        $this->displayed = $displayed;
        $this->widgetConfiguration = $widgetConfiguration;
        $this->htmlStyles = $htmlStyles;
    }

    public static function for(BindingPlace $bindingPlace): self
    {
        $widgetConfiguration = new WidgetConfiguration($bindingPlace);
        $widgetConfiguration->setSize(Size::getDefault());

        return new self($widgetConfiguration);
    }

    public function getWidgetConfiguration(): WidgetConfigurationInterface
    {
        return $this->widgetConfiguration ?? new WidgetConfiguration($this->bindingPlace);
    }

    public function setWidgetConfiguration(?WidgetConfigurationInterface $widgetConfiguration): self
    {
        $this->widgetConfiguration = $widgetConfiguration;

        return $this;
    }

    public function isDisplayed(): bool
    {
        return true === $this->displayed;
    }

    public function getDisplayed(): ?bool
    {
        return $this->displayed;
    }

    public function setDisplayed(?bool $displayed): self
    {
        $this->displayed = $displayed;

        return $this;
    }

    public function getHtmlStyles(): HtmlStyles
    {
        return $this->htmlStyles ?? new HtmlStyles();
    }

    public function setHtmlStyles(?HtmlStyles $htmlStyles): self
    {
        $this->htmlStyles = $htmlStyles;

        return $this;
    }

    public function __clone()
    {
        if (null !== $this->widgetConfiguration) {
            $this->widgetConfiguration = clone $this->widgetConfiguration;
        }

        if (null !== $this->htmlStyles) {
            $this->htmlStyles = clone $this->htmlStyles;
        }
    }
}
