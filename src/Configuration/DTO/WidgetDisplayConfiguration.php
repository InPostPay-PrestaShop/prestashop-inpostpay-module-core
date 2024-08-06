<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\WidgetDisplayConfigurationInterface;
use izi\prestashop\View\Widget\Configuration;
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
     * @var Configuration|null
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

    public function __construct(Configuration $widgetConfiguration, bool $displayed = false, ?HtmlStyles $htmlStyles = null)
    {
        $this->bindingPlace = $widgetConfiguration->getBindingPlace();
        $this->displayed = $displayed;
        $this->widgetConfiguration = $widgetConfiguration;
        $this->htmlStyles = $htmlStyles;
    }

    public static function for(BindingPlace $bindingPlace): self
    {
        $widgetConfiguration = Configuration::for($bindingPlace);

        return new self($widgetConfiguration);
    }

    public function getWidgetConfiguration(): Configuration
    {
        return $this->widgetConfiguration ?? new Configuration($this->bindingPlace, false);
    }

    public function setWidgetConfiguration(?Configuration $widgetConfiguration): self
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
