<?php

namespace izi\prestashop\View\Widget;

/**
 * @template T of WidgetConfigurationInterface
 */
interface WidgetConfigurationResolverInterface
{
    /**
     * @return T
     */
    public function resolve(array $options): WidgetConfigurationInterface;
}
