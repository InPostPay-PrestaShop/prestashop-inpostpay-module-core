<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

final class ThankYouWidgetConfiguration implements ThankYouWidgetConfigurationInterface
{
    private const INPOST_PAY_THANK_YOU_DISPLAY = 'INPOST_PAY_THANK_YOU_DISPLAY';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    public function __construct(ShopAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function shouldDisplayHook($hookName): bool
    {
        return $this->configuration->get(self::INPOST_PAY_THANK_YOU_DISPLAY) === $hookName;
    }
}
