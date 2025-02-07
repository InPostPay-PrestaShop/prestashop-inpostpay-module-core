<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Common\BindingPlace;

/**
 * @template-extends \IteratorAggregate<string, string> HTML attribute values by name
 */
interface WidgetConfigurationInterface extends \IteratorAggregate, \JsonSerializable
{
    public function getBindingPlace(): BindingPlace;

    public function getProductId(): ?string;

    /**
     * @return $this
     */
    public function setProductId(?string $productId): self;
}
