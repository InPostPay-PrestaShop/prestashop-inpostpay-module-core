<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Event;

use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Event\Event;

final class GetBasketRequestEvent extends Event
{
    /**
     * @var BasketSessionInterface
     */
    private $session;

    public function __construct(BasketSessionInterface $session)
    {
        $this->session = $session;
    }

    public function getSession(): BasketSessionInterface
    {
        return $this->session;
    }
}
