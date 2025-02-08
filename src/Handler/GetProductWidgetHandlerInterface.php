<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetProductWidgetCommand;
use izi\prestashop\Handler\Result\ProductWidgetResult;

/**
 * @deprecated
 */
interface GetProductWidgetHandlerInterface
{
    public function __invoke(GetProductWidgetCommand $command): ProductWidgetResult;
}
