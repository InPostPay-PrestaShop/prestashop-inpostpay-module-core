<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Handler\GetProductsHandler;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see GetProductsHandler
 */
final class GetProductsCommand
{
    /**
     * @var int
     *
     * @Assert\GreaterThan(0)
     */
    private $shopId;

    /**
     * @var int|null
     *
     * @Assert\GreaterThanOrEqual(value=0, message="Page index must be greater than or equal to 0.")
     */
    private $pageIndex;

    /**
     * @var int|null
     *
     * @Assert\Range(
     *     min=1,
     *     max=100,
     *     minMessage="Page size must be at least {{ limit }}.",
     *     maxMessage="Page size cannot be greater than {{ limit }}."
     * )
     */
    private $pageSize;

    /**
     * @var string[]|null
     *
     * @Assert\Count(
     *     max=100,
     *     maxMessage="Cannot request more than {{ limit }} products at once."
     * )
     */
    private $productIds;

    /**
     * @param int|null $pageIndex
     * @param int|null $pageSize
     * @param string[]|null $productIds
     */
    public function __construct(int $shopId, ?int $pageIndex = null, ?int $pageSize = null, ?array $productIds = null)
    {
        $this->shopId = $shopId;
        $this->pageIndex = $pageIndex;
        $this->pageSize = $pageSize;
        $this->productIds = $productIds;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function getPageIndex(): ?int
    {
        return $this->pageIndex;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * @return string[]|null
     */
    public function getProductIds(): ?array
    {
        return $this->productIds;
    }
}
