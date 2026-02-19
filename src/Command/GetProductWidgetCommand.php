<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Handler\GetProductWidgetHandler;

/**
 * @see GetProductWidgetHandler
 */
final class GetProductWidgetCommand
{
    /**
     * @var string
     */
    private $hookName;

    /**
     * @var int
     */
    private $productId;

    /**
     * @var int|null
     */
    private $productAttributeId;

    /**
     * @param string $hookName
     * @param int $productId
     * @param int|null $productAttributeId
     */
    public function __construct(string $hookName, int $productId, ?int $productAttributeId = null)
    {
        $this->hookName = $hookName;
        $this->productId = $productId;
        $this->productAttributeId = $productAttributeId;
    }

    public function getHookName(): string
    {
        return $this->hookName;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductAttributeId(): ?int
    {
        return $this->productAttributeId;
    }
}
