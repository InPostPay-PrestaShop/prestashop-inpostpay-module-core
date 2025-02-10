<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class ProductionEnvironment implements EnvironmentInterface
{
    private const WIDGET_V1_JS_URL = 'https://izi.inpost.pl/inpostizi.js';
    private const WIDGET_V2_JS_URL = 'https://inpostpay-widget-v2.inpost.pl/inpostpay.widget.v2.js';

    /**
     * @var bool
     */
    private $isWidgetV2;

    /**
     * @param bool $isWidgetV2 The parameter is deprecated since version 1.11.0. It will have no effect from version 3 of the module.
     */
    public function __construct(bool $isWidgetV2 = false)
    {
        $this->isWidgetV2 = $isWidgetV2;
    }

    public function getType(): EnvironmentType
    {
        return EnvironmentType::Production();
    }

    public function getBasketAppApiUri(): string
    {
        return 'https://api.inpost.pl';
    }

    public function getAuthServerTokenEndpointUri(): string
    {
        return 'https://login.inpost.pl/auth/realms/external/protocol/openid-connect/token';
    }

    public function getWidgetJavaScriptUri(): string
    {
        return $this->isWidgetV2 ? self::WIDGET_V2_JS_URL : self::WIDGET_V1_JS_URL;
    }

    /**
     * @deprecated
     */
    public function getDeepLinkUri(): string
    {
        return 'inpost://izilink';
    }

    /**
     * @internal
     */
    public function getWidgetVersion(): string
    {
        return $this->isWidgetV2 ? '2.0' : '1.0';
    }
}
