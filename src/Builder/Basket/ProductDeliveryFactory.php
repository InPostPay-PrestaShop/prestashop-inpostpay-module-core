<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Basket\Quantity;
use izi\prestashop\Common\Basket\Summary;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Dimensions;
use izi\prestashop\Common\Price;
use izi\prestashop\Common\Product\DeliveryProduct;
use izi\prestashop\Common\Product\DeliveryRelatedProducts;
use izi\prestashop\Common\Weight;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\CarrierRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\Shipping\CartTotal\CartTotalDeliveryStrategyInterface;
use izi\prestashop\Shipping\CartWeight\CartWeightDeliveryStrategyInterface;
use izi\prestashop\Shipping\ProductDimensions\ProductDimensionsDeliveryStrategyInterface;
use izi\prestashop\Shipping\ProductRestriction\ProductRestrictionDeliveryInterface;

class ProductDeliveryFactory
{
    private $cartBaseWeight = [];

    /**
     * @var ShippingConfigurationInterface
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface<\Carrier>
     */
    private $carrierRepository;

    /**
     * @var CartTotalDeliveryStrategyInterface
     */
    private $cartTotalDeliveryStrategy;

    /**
     * @var CartWeightDeliveryStrategyInterface
     */
    private $cartWeightDeliveryStrategy;

    /**
     * @var ProductDimensionsDeliveryStrategyInterface
     */
    private $productDimensionsDeliveryStrategy;

    /**
     * @var ProductRestrictionDeliveryInterface
     */
    private $productRestrictionDelivery;

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(
        ShippingConfigurationInterface $configuration,
        ObjectRepositoryInterface $carrierRepository,
        CartTotalDeliveryStrategyInterface $cartTotalDeliveryStrategy,
        CartWeightDeliveryStrategyInterface $cartWeightDeliveryStrategy,
        ProductDimensionsDeliveryStrategyInterface $productDimensionsDeliveryStrategy,
        ProductRestrictionDeliveryInterface $productRestrictionDelivery
    ) {
        $this->configuration = $configuration;
        $this->carrierRepository = $carrierRepository;
        $this->cartTotalDeliveryStrategy = $cartTotalDeliveryStrategy;
        $this->cartWeightDeliveryStrategy = $cartWeightDeliveryStrategy;
        $this->productDimensionsDeliveryStrategy = $productDimensionsDeliveryStrategy;
        $this->productRestrictionDelivery = $productRestrictionDelivery;
    }

    public function createForCartProduct(
        DeliveryType $deliveryType,
        \Cart $cart,
        \Product $product,
        Price $unitPrice,
        float $weight,
        Quantity $quantity
    ): DeliveryProduct {
        $totalPrice = $unitPrice->multiply($quantity->getQuantity());
        $totalWeight = (new Weight($weight))->multiply($quantity->getQuantity());
        $isDeliveryOptionAvailable = $this->isDeliveryOptionAvailable($product, $deliveryType, $totalPrice, $totalWeight, $cart);

        return new DeliveryProduct($deliveryType, $isDeliveryOptionAvailable);
    }

    public function createForRelatedProduct(
        DeliveryOption $deliveryOption,
        Summary $summary,
        \Cart $cart,
        \Product $product,
        Price $productPrice,
        Quantity $quantity
    ): DeliveryRelatedProducts {
        $deliveryType = $deliveryOption->getType();
        $basketNewPrice = $this->getCartTotalWithNewProduct($productPrice, $quantity, $this->getCartBasePrice($summary));
        $basketNewWeight = $this->getCartWeightWithNewProduct($product, $quantity, $cart);
        $isDeliveryOptionAvailable = $this->isDeliveryOptionAvailable($product, $deliveryType, $basketNewPrice, $basketNewWeight, $cart);
        $isFreeDelivery = $isDeliveryOptionAvailable && $this->isFreeDelivery($deliveryOption, $basketNewPrice); // if delivery option is not available, free delivery is not possible

        return new DeliveryRelatedProducts(
            $deliveryType,
            $isDeliveryOptionAvailable,
            $isFreeDelivery
        );
    }

    private function isDeliveryOptionAvailable(
        \Product $product,
        DeliveryType $deliveryType,
        Price $basketNewPrice,
        Weight $basketNewWeight,
        \Cart $cart
    ): bool {
        $options = $this->configuration->getShippingOptions($deliveryType, (int) $cart->id_shop);
        $referenceId = $options->getCarrierMapping()->getReferenceId();

        if (null === $referenceId || null === $carrier = $this->carrierRepository->findOneByReferenceId($referenceId)) {
            return false;
        }

        if (false === $this->productRestrictionDelivery->isShippingAvailableBasedOnProductCarrierRestriction($carrier, $product)) {
            return false;
        }

        if (false === $this->productDimensionsDeliveryStrategy->isShippingAvailableBasedOnProductDimensions($carrier, new Dimensions((float) $product->width, (float) $product->height, (float) $product->depth))) {
            return false;
        }

        if (false === $this->cartTotalDeliveryStrategy->isShippingAvailableBasedOnTotalPrice($carrier, $basketNewPrice)) {
            return false;
        }

        return $this->cartWeightDeliveryStrategy->isShippingAvailableBasedOnTotalWeight($carrier, $basketNewWeight);
    }

    private function isFreeDelivery(DeliveryOption $deliveryOption, Price $basketNewPrice): bool
    {
        if (null === $deliveryOption->getFreeDeliveryMinimumGrossPrice() || 0 >= $deliveryOption->getFreeDeliveryMinimumGrossPrice()->getPriceAmount()) {
            return false;
        }

        return $basketNewPrice->getGross() >= $deliveryOption->getFreeDeliveryMinimumGrossPrice()->getPriceAmount();
    }

    private function getCartTotalWithNewProduct(Price $productPrice, Quantity $quantity, Price $basketBasePrice): Price
    {
        $productTotalPrice = $productPrice->multiply($quantity->getQuantity());

        return $basketBasePrice->add($productTotalPrice);
    }

    private function getCartWeightWithNewProduct(\Product $product, Quantity $quantity, \Cart $cart): Weight
    {
        $productWeight = new Weight((float) $product->weight);
        $defaultCombinationId = (int) \Product::getDefaultAttribute($product->id);
        $cartWeight = $this->getCartBaseWeight($cart);

        if (0 < $defaultCombinationId) {
            $combination = new \Combination($defaultCombinationId);
            $productWeight = $productWeight->add(new Weight((float) $combination->weight));
        }

        return $cartWeight->add($productWeight->multiply($quantity->getQuantity()));
    }

    private function getCartBasePrice(Summary $summary): Price
    {
        return $summary->getPromoPrice() ?? $summary->getBasePrice();
    }

    private function getCartBaseWeight(\Cart $cart): Weight
    {
        if (!isset($this->cartBaseWeight[$cart->id])) {
            $this->cartBaseWeight[$cart->id] = new Weight((float) $cart->getTotalWeight());
        }

        return $this->cartBaseWeight[$cart->id];
    }
}
