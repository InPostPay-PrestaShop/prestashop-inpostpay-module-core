<?php

declare(strict_types=1);

namespace izi\prestashop;

interface CommandBusInterface
{
    /**
     * @return mixed result returned by command handler
     */
    public function handle(object $command);
}
