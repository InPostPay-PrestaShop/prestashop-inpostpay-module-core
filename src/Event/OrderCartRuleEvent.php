<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

final class OrderCartRuleEvent extends Event
{
    public const PRE_PERSIST = 'inpostizi.order_cart_rule.pre_persist';

    /**
     * @var \OrderCartRule
     */
    private $rule;

    public function __construct(\OrderCartRule $rule)
    {
        $this->rule = $rule;
    }

    public function getRule(): \OrderCartRule
    {
        return $this->rule;
    }
}
