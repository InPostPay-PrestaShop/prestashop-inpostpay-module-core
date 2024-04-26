<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\CheckStatusCommand;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\Handler\Config\Status\StatusCheckerInterface;

final class CheckStatusHandler implements CheckStatusHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var iterable<StatusCheckerInterface>
     */
    private $statusCheckers;

    /**
     * @param iterable<StatusCheckerInterface> $statusCheckers
     */
    public function __construct(iterable $statusCheckers)
    {
        $this->statusCheckers = $statusCheckers;
    }

    public function __invoke(CheckStatusCommand $command): ModuleStatus
    {
        $errors = [];

        foreach ($this->statusCheckers as $statusChecker) {
            $errors[] = $statusChecker->checkStatus();
        }

        return new ModuleStatus(...array_merge(...$errors));
    }
}
