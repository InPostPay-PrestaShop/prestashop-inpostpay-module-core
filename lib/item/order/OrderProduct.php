<?php

namespace izi\item\order;

use izi\item\BasketProduct;
use izi\item\Product;

class OrderProduct extends Product
{
    /**
     * @var OrderQuantity
     */
    protected $quantity;

    /**
     * @param BasketProduct|\stdClass $basketProduct
     */
    public static function fromBasketProduct($basketProduct): self
    {
        $product = new self();

        $product->product_id = $basketProduct->product_id;
        $product->product_category = $basketProduct->product_category;
        $product->ean = $basketProduct->ean;
        $product->product_name = $basketProduct->product_name;
        $product->product_description = $basketProduct->product_description;
        $product->product_link = $basketProduct->product_link;
        $product->product_image = $basketProduct->product_image;
        $product->base_price = $basketProduct->promo_price;
        $product->product_attributes = $basketProduct->product_attributes;
        $product->variants = $basketProduct->variants;
        $product->quantity = OrderQuantity::fromBasketQuantity($basketProduct->quantity);

        return $product;
    }
}
