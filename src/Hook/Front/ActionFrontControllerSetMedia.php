<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\View\Asset\AssetManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ActionFrontControllerSetMedia implements HookInterface
{
    public const HOOK_NAME = 'actionFrontControllerSetMedia';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var AssetManagerInterface
     */
    private $assetManager;

    public function __construct(
        \Module $module,
        \Context $context,
        EnvironmentInterface $environment,
        AssetManagerInterface $assetManager
    )
    {
        $this->module = $module;
        $this->context = $context;
        $this->environment = $environment;
        $this->assetManager = $assetManager;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        $request = $parameters['request'] ?? null;

        if ($request instanceof Request && $request->isXmlHttpRequest()) {
            return;
        }

        $this->assetManager
            ->registerJavaScript($this->environment->getWidgetJavaScriptUri(), [
                'id' => 'inpostpay-widget',
                'position' => 'bottom',
                'priority' => 100,
            ])
            ->registerJavascript('js/prestashopizi.js', [
                'position' => 'bottom',
                'priority' => 101,
            ]);

        $this->assetManager->registerJavaScriptVariables([
            'inpostizi_backend_ajax_url' => $this->context->link->getModuleLink($this->module->name, 'backend'),
            'inpostizi_cart_ajax_url' => $this->context->link->getModuleLink($this->module->name, 'cart'),
            'inpostizi_generic_http_error' => $this->module->l('Something went wrong. Please try again later.', self::HOOK_NAME),
        ]);

        if ($this->context->controller instanceof \ProductControllerCore) {
            $productObject = $this->context->controller->getProduct();

            if (\Validate::isLoadedObject($productObject)) {
                $this->assetManager->registerJavaScriptVariables([
                    'inpostizi_product_page_id_product' => $productObject->id,
                ]);
            }
        }
    }
}
