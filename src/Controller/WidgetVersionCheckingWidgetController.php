<?php

namespace izi\prestashop\Controller;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\WidgetVersionCheckerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final class WidgetVersionCheckingWidgetController
{
    use WidgetVersionCheckerTrait;

    private const DEPRECATED_V1_CONTROLLERS = [
        'getDeepLink',
        'getPayData',
        'getOrderComplete',
        'getIsBound',
        'deleteBinding',
        'getWidgetHook',
    ];

    private const V2_CONTROLLERS = [
        'getBindingKey',
        'getOrderConfirmationUrl',
    ];

    /**
     * @var WidgetController
     */
    private $controller;

    /**
     * @param WidgetController $controller
     */
    public function __construct($controller, ApiConfigurationInterface $apiConfiguration)
    {
        $this->controller = $controller;
        $this->apiConfiguration = $apiConfiguration;
    }

    public function __call(string $name, array $arguments): Response
    {
        if ($this->isEndpointDisabled($name)) {
            return new JsonResponse([
                'message' => 'This endpoint is disabled.',
            ], 403);
        }

        return $this->controller->$name(...$arguments);
    }

    private function isEndpointDisabled(string $name): bool
    {
        $disabledControllers = $this->isWidgetV2Enabled()
            ? self::DEPRECATED_V1_CONTROLLERS
            : self::V2_CONTROLLERS;

        return in_array($name, $disabledControllers, true);
    }
}
