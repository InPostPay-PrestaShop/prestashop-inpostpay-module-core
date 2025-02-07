<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetBindingConfirmationCommand;
use izi\prestashop\Handler\Result\BindingConfirmationStream;

/**
 * @deprecated
 */
interface GetBindingConfirmationHandlerInterface
{
    public function __invoke(GetBindingConfirmationCommand $command): BindingConfirmationStream;
}
