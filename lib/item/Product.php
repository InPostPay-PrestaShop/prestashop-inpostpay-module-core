<?php

namespace izi\item;

abstract class Product extends \izi\Item
{
    /**
     * @var string
     */
    protected $product_id;

    /**
     * @var string|null
     */
    protected $product_category;

    /**
     * @var string|null
     */
    protected $ean;

    /**
     * @var string
     */
    protected $product_name;

    /**
     * @var string|null
     */
    protected $product_description;

    /**
     * @var string|null
     */
    protected $product_link;

    /**
     * @var string|null
     */
    protected $product_image;

    /**
     * @var Price
     */
    protected $base_price;

    /**
     * @var Quantity
     */
    protected $quantity;

    /**
     * @var ProductAttribute[]|null
     */
    protected $product_attributes;

    /**
     * @var Variant[]|null
     */
    protected $variants;
}
