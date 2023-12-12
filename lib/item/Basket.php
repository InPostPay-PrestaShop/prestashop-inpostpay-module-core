<?php

namespace izi\item;

class Basket extends \izi\Item
{
    protected $browser_id;

    /**
     * @var Summary
     */
    protected $summary;
    protected $delivery;
    protected $promo_codes;
    protected $products;
    protected $related_products;
    protected $consents;

    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setNotice(BasketNotice $notice): self
    {
        $this->summary->basket_notice = $notice;

        return $this;
    }
}
