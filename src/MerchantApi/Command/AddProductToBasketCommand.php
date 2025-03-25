<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Handler\AddProductToBasketHandler;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketId;

/**
 * Add a product to a basket with a given ID or create a new basket and add the product.
 *
 * @see AddProductToBasketHandler
 */
class AddProductToBasketCommand
{
    /**
     * @var string
     */
    private $productId;

    /**
     * @var BasketId
     */
    private $request;

    public function __construct(string $productId, BasketId $request)
    {
        $this->productId = $productId;
        $this->request = $request;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getRequest(): BasketId
    {
        return $this->request;
    }
}
