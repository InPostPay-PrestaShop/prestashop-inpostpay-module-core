<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider\Admin;

use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use izi\prestashop\View\Asset\Provider\DTO\Assets;
use Symfony\Component\HttpFoundation\RequestStack;

final class CartRulesAssetsProvider implements AssetsProviderInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(\Context $context, RequestStack $requestStack)
    {
        $this->context = $context;
        $this->requestStack = $requestStack;
    }

    public function getAssets(): ?Assets
    {
        if (!$this->context->controller instanceof \AdminCartRulesControllerCore) {
            return null;
        }

        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        if (!$request->query->has('addcart_rule') && !$request->query->has('updatecart_rule')) {
            return null;
        }

        return (new Assets())->addJavaScript('js/admin/cart-rules.js');
    }
}
