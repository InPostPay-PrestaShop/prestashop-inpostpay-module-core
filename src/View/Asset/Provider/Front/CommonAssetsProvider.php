<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider\Front;

use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use izi\prestashop\View\Asset\Provider\DTO\Assets;

final class CommonAssetsProvider implements AssetsProviderInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    public function __construct(\Context $context, EnvironmentInterface $environment)
    {
        $this->context = $context;
        $this->environment = $environment;
    }

    public function getAssets(): Assets
    {
        return (new Assets())
            ->addJavaScript($this->environment->getWidgetJavaScriptUri(), [
                'id' => 'inpostpay-widget',
                'position' => 'bottom',
                'priority' => 100,
            ])
            ->addJavaScript('v2.js', [
                'position' => 'bottom',
                'priority' => 101,
            ])
            ->addJavaScriptVariable('inpostizi_backend_ajax_url', $this->context->link->getModuleLink('inpostizi', 'backend'))
            ->addJavaScriptVariable('inpostizi_generic_http_error', $this->context->getTranslator()->trans('Something went wrong. Please try again later.', [], 'Modules.Inpostizi.Errors'));
    }
}
