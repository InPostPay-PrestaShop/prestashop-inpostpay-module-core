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
     * @param string $hookName
     * @param int $productId
     */
    public function __construct(string $hookName, int $productId)
    {
        $this->hookName = $hookName;
        $this->productId = $productId;
    }

    public function getHookName(): string
    {
        return $this->hookName;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }
}
