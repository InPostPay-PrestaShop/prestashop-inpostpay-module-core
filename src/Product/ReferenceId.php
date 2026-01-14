<?php

declare(strict_types=1);

namespace izi\prestashop\Product;

final class ReferenceId implements \Stringable
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var int|null
     */
    private $combinationId;

    /**
     * @var int|null
     */
    private $customizationId;

    /**
     * @var string|null
     */
    private $originalReferenceId;

    private function __construct(int $productId, ?int $combinationId = null, ?int $customizationId = null)
    {
        $this->productId = $productId;
        $this->combinationId = $combinationId;
        $this->customizationId = $customizationId;
    }

    public static function create(int $productId, ?int $combinationId = null, ?int $customizationId = null): self
    {
        return new self($productId, $combinationId, $customizationId);
    }

    public static function fromString(string $referenceId): ?self
    {
        $parts = explode('.', $referenceId);

        if (1 === \count($parts)) {
            $result = is_numeric($parts[0]) ? self::create((int) $parts[0]) : null;
        } elseif (2 === \count($parts)) {
            $result = is_numeric($parts[0]) && is_numeric($parts[1]) ? self::create((int) $parts[0], (int) $parts[1]) : null;
        } elseif (3 === \count($parts) && is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
            $result = self::create((int) $parts[0], (int) $parts[1], (int) $parts[2]);
        } else {
            $result = null;
        }

        if (null !== $result) {
            $result->originalReferenceId = $referenceId;
        }

        return $result;
    }

    public function __toString(): string
    {
        if (null !== $this->originalReferenceId) {
            return $this->originalReferenceId;
        }

        if (null === $this->customizationId) {
            return \sprintf('%d.%d', $this->productId, $this->combinationId);
        }

        return \sprintf('%d.%d.%d', $this->productId, $this->combinationId, $this->customizationId);
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCombinationId(): ?int
    {
        if (0 === (int) $this->combinationId) {
            return null;
        }

        return $this->combinationId;
    }

    public function getCustomizationId(): ?int
    {
        if (0 === (int) $this->customizationId) {
            return null;
        }

        return $this->customizationId;
    }

    public function hasCustomization(): bool
    {
        return 0 !== (int) $this->customizationId;
    }
}
