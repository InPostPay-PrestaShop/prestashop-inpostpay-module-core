<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\Response\ClientDetails;
use izi\prestashop\Command\GetClientDetailsCommand;

interface GetClientDetailsHandlerInterface
{
    public function __invoke(GetClientDetailsCommand $command): ?ClientDetails;
}
