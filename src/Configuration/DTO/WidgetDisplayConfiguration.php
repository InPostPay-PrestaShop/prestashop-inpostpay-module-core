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
     */
    private $displayed;

    /**
     * @var Configuration|null
     *
     * @Assert\NotNull()
     *
     * @Assert\Valid()
     */
    private $widgetConfiguration;

    /**
     * @var HtmlStyles|null
     *
     * @Assert\NotNull()
     *
     * @Assert\Valid()
     */
    private $htmlStyles;

    public function __construct(BindingPlace $bindingPlace, bool $displayed = false, ?Configuration $widgetConfiguration = null, ?HtmlStyles $htmlStyles = null)
    {
        $this->bindingPlace = $bindingPlace;
        $this->displayed = $displayed;
        $this->widgetConfiguration = $widgetConfiguration;
        $this->htmlStyles = $htmlStyles;
    }

    public function getBinding(): BindingPlace
    {
        return $this->bindingPlace;
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
}
