<?php

namespace izi\item\order;

class OrderDetails extends \izi\Item
{
    protected $order_comments;
    protected $order_id;
    protected $pos_id;
    protected $order_creation_date;
    protected $basket_id;
    protected $order_merchant_status_description;
    protected $payment_type;
    protected $order_base_price;
    protected $order_final_price;
    protected $order_discount;
    protected $currency;
    protected $delivery_references_list;
    protected $customer_order_id;
}
