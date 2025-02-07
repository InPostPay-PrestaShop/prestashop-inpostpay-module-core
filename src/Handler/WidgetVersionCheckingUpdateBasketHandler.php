<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\WidgetVersionCheckerTrait;

/**
 * @internal
 */
final class WidgetVersionCheckingUpdateBasketHandler implements UpdateBasketHandlerInterface
{
    use CommandHandlerTrait;
    use WidgetVersionCheckerTrait;

    /**
     * @var UpdateBasketHandlerInterface
     */
    private $v1Handler;

    /**
     * @var UpdateBasketHandlerInterface
     */
    private $v2Handler;

    public function __construct(ApiConfigurationInterface $apiConfiguration, UpdateBasketHandlerInterface $v1Handler, UpdateBasketHandlerInterface $v2Handler)
    {
        $this->apiConfiguration = $apiConfiguration;
        $this->v1Handler = $v1Handler;
        $this->v2Handler = $v2Handler;
    }

    public function __invoke(UpdateBasketCommand $command)
    {
        if ($this->isWidgetV2Enabled()) {
            return ($this->v2Handler)($command);
        }

        return ($this->v1Handler)($command);
    }
}
