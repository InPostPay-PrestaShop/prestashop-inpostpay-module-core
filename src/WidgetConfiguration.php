<?php

namespace izi\prestashop;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Widget\Alignment;
use izi\prestashop\Widget\FrameStyle;
use izi\prestashop\Widget\Language;
use izi\prestashop\Widget\Variant;

/**
 * @implements \IteratorAggregate<string, string>
 */
final class WidgetConfiguration implements \IteratorAggregate, \JsonSerializable
{
    public const WIDTH_MIN_PX = 220;
    public const WIDTH_MAX_PX = 600;

    /**
     * @var BindingPlace|null
     */
    private $bindingPlace;

    /**
     * @var Language|null
     */
    private $language;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $productId;

    /**
     * @var string|null
     */
    private $maskedPhoneNumber;

    /**
     * @var Variant|null
     */
    private $variant;

    /**
     * @var bool
     */
    private $basket;

    /**
     * @var bool
     */
    private $darkMode = false;

    /**
     * @var int|null
     */
    private $count;

    /**
     * @var Alignment|null
     */
    private $alignment;

    /**
     * @var int|null
     */
    private $minWidth;

    /**
     * @var int|null
     */
    private $maxWidth;

    /**
     * @var FrameStyle|null
     */
    private $frameStyle;

    public function __construct(BindingPlace $bindingPlace = null, bool $basket = false)
    {
        $this->bindingPlace = $bindingPlace;
        $this->basket = $basket;
    }

    public function getBindingPlace(): BindingPlace
    {
        return $this->bindingPlace ?? BindingPlace::ProductCard();
    }

    public function setBindingPlace(BindingPlace $bindingPlace): self
    {
        $this->bindingPlace = $bindingPlace;

        return $this;
    }

    public function getLanguage(): Language
    {
        return $this->language ?? Language::Pl();
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getMaskedPhoneNumber(): ?string
    {
        return $this->maskedPhoneNumber;
    }

    public function setMaskedPhoneNumber(?string $maskedPhoneNumber): self
    {
        $this->maskedPhoneNumber = $maskedPhoneNumber;

        return $this;
    }

    public function getVariant(): Variant
    {
        return $this->variant ?? Variant::Secondary();
    }

    public function setVariant(Variant $variant): self
    {
        $this->variant = $variant;

        return $this;
    }

    public function isBasket(): bool
    {
        return $this->basket;
    }

    public function setBasket(bool $basket): self
    {
        $this->basket = $basket;

        return $this;
    }

    public function isDarkMode(): bool
    {
        return $this->darkMode;
    }

    public function setDarkMode(bool $darkMode): self
    {
        $this->darkMode = $darkMode;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }


    public function getAlignment(): ?Alignment
    {
        return $this->alignment;
    }

    public function setAlignment(?Alignment $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    public function getMinWidthPx(): ?int
    {
        return $this->minWidth;
    }

    public function setMinWidth(?int $minWidthPx): self
    {
        $this->assertIsValidWidth($minWidthPx);
        $this->minWidth = $minWidthPx;

        return $this;
    }

    public function getMaxWidthPx(): ?int
    {
        return $this->maxWidth;
    }

    public function setMaxWidth(?int $maxWidthPx): self
    {
        $this->assertIsValidWidth($maxWidthPx);
        $this->maxWidth = $maxWidthPx;

        return $this;
    }

    public function getFrameStyle(): ?FrameStyle
    {
        return $this->frameStyle;
    }

    public function setFrameStyle(?FrameStyle $frameStyle): self
    {
        $this->frameStyle = $frameStyle;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), static function ($value) {
            return null !== $value;
        });
    }

    /**
     * @return \Generator<string, string>
     */
    public function getIterator(): \Generator
    {
        yield 'binding_place' => $this->getBindingPlace()->value;
        yield 'variant' => $this->getVariant()->value;
        yield 'language' => $this->getLanguage()->value;

        if ($this->basket) {
            yield 'basket' => 'true';
        }

        if ($this->darkMode) {
            yield 'dark_mode' => 'true';
        }

        if (null !== $this->productId) {
            yield 'data-product-id' => $this->productId;
        }

        if (null !== $this->name) {
            yield 'name' => $this->name;
        }

        if (null !== $this->maskedPhoneNumber) {
            yield 'masked_phone_number' => $this->maskedPhoneNumber;
        }

        if (null !== $this->count) {
            yield 'count' => $this->count;
        }

        if (null !== $this->alignment) {
            yield 'class' => $this->alignment->getClass();
        }

        if (null !== $this->minWidth) {
            yield 'style' => sprintf('min-width: %dpx;', $this->minWidth);
        }

        if (null !== $this->maxWidth) {
            yield 'max_width' => (string) $this->maxWidth;
        }

        if (null !== $this->frameStyle) {
            yield 'frame_style' => $this->frameStyle->value;
        }
    }

    private function assertIsValidWidth(?int $width): void
    {
        if (null === $width || self::WIDTH_MIN_PX <= $width && self::WIDTH_MAX_PX >= $width) {
            return;
        }

        throw new \UnexpectedValueException(sprintf('Widget width should be between %d and %d px, %d given.', self::WIDTH_MIN_PX, self::WIDTH_MAX_PX, $width));
    }
}
