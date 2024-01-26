<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Common\BindingPlace;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements \IteratorAggregate<string, string>
 */
final class Configuration implements \IteratorAggregate, \JsonSerializable
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
     *
     * @Assert\Range(min=Configuration::WIDTH_MIN_PX, max=Configuration::WIDTH_MAX_PX)
     */
    private $minWidthPx;

    /**
     * @var int|null
     *
     * @Assert\Range(min=Configuration::WIDTH_MIN_PX, max=Configuration::WIDTH_MAX_PX)
     */
    private $maxWidthPx;

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
        if (0 > $count) {
            throw new \DomainException(sprintf('Product count should be greater than or equal 0, %d given.', $count));
        }

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
        return $this->minWidthPx;
    }

    public function setMinWidthPx(?int $minWidth): self
    {
        $this->minWidthPx = $minWidth;

        return $this;
    }

    public function getMaxWidthPx(): ?int
    {
        return $this->maxWidthPx;
    }

    public function setMaxWidthPx(?int $maxWidth): self
    {
        $this->maxWidthPx = $maxWidth;

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
            yield 'count' => (string) $this->count;
        }

        if (null !== $this->alignment) {
            yield 'class' => $this->alignment->getHtmlClass();
        }

        if (null !== $this->minWidthPx) {
            yield 'style' => sprintf('min-width: %dpx;', $this->minWidthPx);
        }

        if (null !== $this->maxWidthPx) {
            yield 'max_width' => (string) $this->maxWidthPx;
        }

        if (null !== $this->frameStyle) {
            yield 'frame_style' => $this->frameStyle->value;
        }
    }
}
