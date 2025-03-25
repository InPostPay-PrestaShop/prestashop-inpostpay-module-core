<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Api;

use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\MerchantApi\Command\GetProductsCommand;
use izi\prestashop\MerchantApi\Model\Product\Response\Products;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ProductController extends AbstractApiController
{
    public function getProducts(Request $request): JsonResponse
    {
        $pageIndex = $request->query->get('page_index');
        $pageSize = $request->query->get('page_size');
        $productIds = $request->query->get('product_ids');

        if (null !== $productIds && !is_array($productIds)) {
            $productIds = explode(',', $productIds);
        }

        $command = new GetProductsCommand(
            $request->attributes->getInt('_inpost_izi_shop_id'),
            $pageIndex ? (int) $pageIndex : null,
            $pageSize ? (int) $pageSize : null,
            $productIds
        );

        /** @var Products $products */
        $products = $this->bus->handle($command);

        return $this->productsResponse($products);
    }

    private function productsResponse(Products $products): JsonResponse
    {
        $data = $this->serializer->serialize($products, 'json', [
            'datetime_format' => BasketAppClientInterface::DATETIME_FORMAT,
            'datetime_timezone' => BasketAppClientInterface::DATETIME_ZONE,
        ]);

        return JsonResponse::create()->setContent($data);
    }
}
