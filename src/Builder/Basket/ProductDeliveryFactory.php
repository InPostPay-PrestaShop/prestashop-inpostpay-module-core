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
        DeliveryType $deliveryType,
        Summary $summary,
        \Cart $cart,
        \Product $product,
        Price $productPrice,
        Quantity $quantity,
        ?float $freeDeliveryAmount = null
    ): DeliveryRelatedProducts {
        $basketNewPrice = $this->getCartTotalWithNewProduct($productPrice, $quantity, $this->getCartBasePrice($summary));
        $basketNewWeight = $this->getCartWeightWithNewProduct($product, $quantity, $cart);
        $isDeliveryOptionAvailable = $this->isDeliveryOptionAvailable($product, $deliveryType, $basketNewPrice, $basketNewWeight, $cart);
        $isFreeDelivery = $isDeliveryOptionAvailable && $this->isFreeDelivery($freeDeliveryAmount, $basketNewPrice); // if delivery option is not available, free delivery is not possible

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

        if (!$carrier->active) {
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

        if (false === $this->cartWeightDeliveryStrategy->isShippingAvailableBasedOnTotalWeight($carrier, $basketNewWeight)) {
            return false;
        }

        // TODO: pass modified product list
        return $this->checkModuleRestrictions($carrier, $cart);
    }

    private function isFreeDelivery(?float $freeDeliveryAmount, Price $basketNewPrice): bool
    {
        if (null === $freeDeliveryAmount) {
            return false;
        }

        return $basketNewPrice->getGross() >= $freeDeliveryAmount;
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

    /**
     * Carrier modules can disable delivery options by returning false as the shipping cost.
     */
    private function checkModuleRestrictions(\Carrier $carrier, \Cart $cart): bool
    {
        if (!$carrier->is_module || !$carrier->shipping_external || $carrier->is_free) {
            return true;
        }

        $shippingCost = (\Closure::bind(function () use ($carrier) {
            $products = $this->getProducts();

            return $this->getPackageShippingCostFromModule($carrier, 10., $products);
        }, $cart, \Cart::class))();

        return false !== $shippingCost;
    }
}
