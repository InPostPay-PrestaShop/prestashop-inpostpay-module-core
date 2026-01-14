<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\View;

use izi\prestashop\BasketApp\Product\Response\Product;
use izi\prestashop\BasketApp\Product\Response\Status;
use izi\prestashop\Common\Product\ProductAttribute;
use izi\prestashop\HotProduct\HotProduct;

final class HotProductView
{
    /**
     * @var string
     */
    private $referenceId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var \DateTimeInterface|null
     */
    private $availableFrom;

    /**
     * @var \DateTimeInterface|null
     */
    private $availableTo;

    /**
     * @var string|null
     */
    private $shop;

    /**
     * @var bool|null
     */
    private $active;

    /**
     * @var bool
     */
    private $exists = true;

    /**
     * @var bool
     */
    private $importable = false;

    private function __construct(string $referenceId, string $name)
    {
        $this->referenceId = $referenceId;
        $this->name = $name;
    }

    public static function local(HotProduct $product, string $name, ?string $shop, ?Product $apiProduct = null): self
    {
        $view = new self((string) $product->getReferenceId(), $name);

        $view->id = $product->getId();
        $view->name = $name;
        $view->availableFrom = $product->getAvailableFrom();
        $view->availableTo = $product->getAvailableTo();
        $view->shop = $shop;

        if (null !== $apiProduct) {
            $view->active = Status::Active() === $apiProduct->getStatus();
        }

        return $view;
    }

    public static function notFound(HotProduct $product, string $name, ?string $shop): self
    {
        $view = self::local($product, $name, $shop);
        $view->exists = false;

        return $view;
    }

    public static function remote(Product $product, bool $importable): self
    {
        $view = new self($product->getId(), self::getRemoteProductName($product));

        $view->importable = $importable;
        $view->active = Status::Active() === $product->getStatus();

        if (null !== $availability = $product->getAvailability()) {
            $view->availableFrom = $availability->getStartDate();
            $view->availableTo = $availability->getEndDate();
        }

        return $view;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvailableFrom(): ?\DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function getAvailableTo(): ?\DateTimeInterface
    {
        return $this->availableTo;
    }

    public function getShop(): ?string
    {
        return $this->shop;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function isImportable(): bool
    {
        return $this->importable;
    }

    private static function getRemoteProductName(Product $product): string
    {
        if ([] === $attributes = $product->getAttributes()) {
            return $product->getName();
        }

        $attributes = array_map(static function (ProductAttribute $attribute): string {
            return \sprintf('%s - %s', $attribute->getName(), $attribute->getValue());
        }, $attributes);

        return \sprintf('%s : %s', $product->getName(), implode(', ', $attributes));
    }
}
