<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\WidgetVersionCheckerTrait;

/**
 * @internal
 */
final class WidgetVersionCheckingWidgetParamsProvider implements WidgetParametersProviderInterface
{
    use WidgetVersionCheckerTrait;

    /**
     * @var WidgetParametersProviderInterface
     */
    private $v1Provider;

    /**
     * @var WidgetParametersProviderInterface
     */
    private $v2Provider;

    public function __construct(ApiConfigurationInterface $apiConfiguration, WidgetParametersProviderInterface $v1Provider, WidgetParametersProviderInterface $v2Provider)
    {
        $this->apiConfiguration = $apiConfiguration;
        $this->v1Provider = $v1Provider;
        $this->v2Provider = $v2Provider;
    }

    public function getParameters(?string $hookName, array $parameters): array
    {
        if ($this->isWidgetV2Enabled()) {
            return $this->v2Provider->getParameters($hookName, $parameters);
        }

        return $this->v1Provider->getParameters($hookName, $parameters);
    }
}
