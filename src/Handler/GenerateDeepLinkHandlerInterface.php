<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GenerateDeepLinkCommand;
use izi\prestashop\Handler\Result\DeepLink;

interface GenerateDeepLinkHandlerInterface
{
    public function __invoke(GenerateDeepLinkCommand $command): DeepLink;
}
