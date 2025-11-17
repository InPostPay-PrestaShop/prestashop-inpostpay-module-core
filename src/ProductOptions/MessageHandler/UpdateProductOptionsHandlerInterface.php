<?php

namespace izi\prestashop\ProductOptions\MessageHandler;

use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;

interface UpdateProductOptionsHandlerInterface
{
    public function __invoke(UpdateProductOptionsCommand $command);
}
