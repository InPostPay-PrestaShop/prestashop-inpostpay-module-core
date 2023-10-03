<?php

namespace izi\item\order;

use izi\item\Price;

class OrderDetails extends \izi\Item
{
    protected $order_comments;
    protected $basket_id;
    protected $order_id;
    protected $pos_id;
    protected $order_creation_date;
    protected $order_update_date;
    protected $merchant_id;
    protected $payment_status;
    protected $order_status;
    protected $order_merchant_status_description;
    protected $order_base_price;
    protected $order_final_price;
    protected $delivery_references_list;
    protected $currency;
    protected $payment_type;
}
