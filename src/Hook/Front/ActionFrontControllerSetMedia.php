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
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var AssetManagerInterface
     */
    private $assetManager;

    public function __construct(EnvironmentInterface $environment, AssetManagerInterface $assetManager)
    {
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
    }
}
