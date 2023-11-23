<?php

namespace izi\item;

use izi\item\order\OrderProduct;

class BasketProduct extends Product
{
    /**
     * @var Price|null
     */
    protected $promo_price;

    /**
     * @var Price|null
     */
    protected $lowest_price;

    /**
     * @var BasketQuantity
     */
    protected $quantity;

    public function asOrderProduct(): OrderProduct
    {
        $product = new OrderProduct();

        $product->product_id = $this->product_id;
        $product->product_category = $this->product_category;
        $product->ean = $this->ean;
        $product->product_name = $this->product_name;
        $product->product_description = $this->product_description;
        $product->product_link = $this->product_link;
        $product->product_image = $this->product_image;
        $product->base_price = $this->base_price;
        $product->product_attributes = $this->product_attributes;
        $product->variants = $this->variants;
        $product->quantity = $this->quantity->asOrderQuantity();

        return $product;
    }
}
