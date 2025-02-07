<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

/**
 * @internal
 */
trait WidgetVersionCheckerTrait
{
    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    private function isWidgetV2Enabled(): bool
    {
        if (!is_callable([$this->apiConfiguration, 'getMerchantClientId'])) {
            @trigger_error(sprintf('Not implementing the "getMerchantClientId()" method in "%s" is deprecated.', get_class($this->apiConfiguration)), \E_USER_DEPRECATED);

            return false;
        }

        if ('' === $this->apiConfiguration->getMerchantClientId()) {
            return false;
        }

        $environment = $this->apiConfiguration->getEnvironment();

        if (!is_callable([$environment, 'getWidgetVersion'])) {
            return false;
        }

        return '2.0' === $environment->getWidgetVersion();
    }
}
