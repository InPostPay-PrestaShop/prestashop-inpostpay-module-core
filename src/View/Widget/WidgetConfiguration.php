<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Common\BindingPlace;
use Symfony\Component\Validator\Constraints as Assert;

final class WidgetConfiguration implements WidgetConfigurationInterface
{
    public const WIDTH_MIN_PX = 220;
    public const WIDTH_MAX_PX = 1200;

    /**
     * @var BindingPlace
     */
    private $bindingPlace;

    /**
     * @var string|null
     */
    private $productId;

    /**
     * @var FrameStyle|null
     */
    private $frameStyle;

    /**
     * @var bool
     */
    private $darkMode = false;

    /**
     * @var Variant|null
     */
    private $variant;

    /**
     * @var Size|null
     */
    private $size;

    /**
     * @var int|null
     *
     * @Assert\Range(min=self::WIDTH_MIN_PX, max=self::WIDTH_MAX_PX)
     */
    private $maxWidthPx;

    public function __construct(BindingPlace $bindingPlace)
    {
        $this->bindingPlace = $bindingPlace;
    }

    public function getBindingPlace(): BindingPlace
    {
        return $this->bindingPlace;
    }

    public function withBindingPlace(BindingPlace $bindingPlace): self
    {
        $clone = clone $this;
        $clone->bindingPlace = $bindingPlace;

        return $clone;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * @return $this
     */
    public function setProductId(?string $productId): WidgetConfigurationInterface
    {
        $this->productId = $productId;

        return $this;
    }

    public function getFrameStyle(): ?FrameStyle
    {
        return $this->frameStyle;
    }

    /**
     * @return $this
     */
    public function setFrameStyle(?FrameStyle $frameStyle): self
    {
        $this->frameStyle = $frameStyle;

        return $this;
    }

    public function isDarkMode(): bool
    {
        return true === $this->darkMode;
    }

    /**
     * @return $this
     */
    public function setDarkMode(?bool $darkMode): self
    {
        $this->darkMode = $darkMode;

        return $this;
    }

    public function getVariant(): Variant
    {
        return $this->variant ?? Variant::Secondary();
    }

    /**
     * @return $this
     */
    public function setVariant(?Variant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function getSize(): ?Size
    {
        return $this->size;
    }

    /**
     * @return $this
     */
    public function setSize(?Size $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getMaxWidthPx(): ?int
    {
        return $this->maxWidthPx;
    }

    /**
     * @return $this
     */
    public function setMaxWidthPx(?int $maxWidth): self
    {
        $this->maxWidthPx = $maxWidth;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), static function ($value) {
            return null !== $value;
        });
    }

    /**
     * @return \Traversable<string, string>
     */
    public function getIterator(): \Traversable
    {
        yield 'binding_place' => $this->bindingPlace->value;

        if (null !== $this->productId) {
            yield 'data-product-id' => $this->productId;
        }

        if (null !== $variation = $this->getVariation()) {
            yield 'variation' => $variation;
        }

        if (null !== $this->maxWidthPx) {
            yield 'style' => sprintf('max-width: %dpx;', $this->maxWidthPx);
        }
    }

    private function getVariation(): ?string
    {
        $parts = [];

        if (null !== $this->frameStyle) {
            $parts[] = $this->frameStyle->value;
        }

        if ($this->isDarkMode()) {
            $parts[] = 'dark';
        }

        if (Variant::Primary() === $variant = $this->getVariant()) {
            $parts[] = $variant->value;
        }

        if (null !== $this->size) {
            $parts[] = $this->size->value;
        }

        if ([] === $parts) {
            return null;
        }

        return implode(' ', $parts);
    }
}
