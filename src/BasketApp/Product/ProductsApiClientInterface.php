<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Product;

use izi\prestashop\BasketApp\PaginationPage;
use izi\prestashop\BasketApp\Product\Exception\MaxProductLimitReachedException;
use izi\prestashop\BasketApp\Product\Exception\ProductExistsException;
use izi\prestashop\BasketApp\Product\Exception\ProductNotFoundException;
use izi\prestashop\BasketApp\Product\Request\CreateProductsRequest;
use izi\prestashop\BasketApp\Product\Response\CreateProductsResponse;
use izi\prestashop\BasketApp\Product\Response\Product as ResponseProduct;
use izi\prestashop\Common\HotProduct\Product;

interface ProductsApiClientInterface
{
    /**
     * Create products in InPost Pay.
     *
     * @throws ProductExistsException
     * @throws MaxProductLimitReachedException
     */
    public function createProducts(CreateProductsRequest $products): CreateProductsResponse;

    /**
     * Get paginated list of products from InPost Pay.
     *
     * @param string[] $productIds optional list of product IDs to filter by
     *
     * @return PaginationPage<ResponseProduct>
     */
    public function getProductsPage(array $productIds = [], ?int $pageSize = null, ?int $pageIndex = null): PaginationPage;

    /**
     * Get InPost Pay products iterator.
     *
     * @param string[] $productIds optional list of product IDs to filter by
     *
     * @return \Traversable<ResponseProduct>
     */
    public function getProducts(array $productIds = [], ?int $pageSize = null): \Traversable;

    /**
     * Update a product in InPost Pay.
     *
     * @throws ProductNotFoundException
     */
    public function updateProduct(string $productId, Product $product): ResponseProduct;

    /**
     * Delete a product from InPost Pay.
     *
     * @throws ProductNotFoundException
     */
    public function deleteProduct(string $productId): void;
}
