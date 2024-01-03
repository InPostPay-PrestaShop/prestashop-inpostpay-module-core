<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\WidgetConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

trait ProductWidgetRendererTrait
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var WidgetInterface
     */
    private $module;

    private function renderWidget(int $productId, array $parameters, string $hookName = null): string
    {
        if (0 >= $productId) {
            return '';
        }

        if (!$this->configuration->isDisplayedOnProductCard()) {
            return '';
        }

        $configuration = $this->configuration
            ->getProductCardConfiguration()
            ->setProductId((string) $productId);

        return $this->module->renderWidget($hookName, [
            'config' => $configuration,
            'request' => $parameters['request'] ?? null,
        ]);
    }
}
