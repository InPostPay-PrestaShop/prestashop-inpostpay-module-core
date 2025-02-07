<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

/**
 * @method EnvironmentType getType()
 */
interface EnvironmentInterface
{
    public function getBasketAppApiUri(): string;

    public function getAuthServerTokenEndpointUri(): string;

    public function getWidgetJavaScriptUri(): string;

    /**
     * @deprecated
     */
    public function getDeepLinkUri(): string;
}
