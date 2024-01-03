<?php

declare(strict_types=1);

namespace izi\prestashop;

interface CommandBusInterface
{
    /**
     * @param object $command
     *
     * @return mixed result returned by command handler
     */
    public function handle($command);
}
