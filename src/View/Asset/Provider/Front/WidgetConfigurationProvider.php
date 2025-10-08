<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider\Front;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use izi\prestashop\View\Asset\Provider\DTO\Assets;

final class WidgetConfigurationProvider implements AssetsProviderInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    /**
     * @var GeneralConfigurationInterface
     */
    private $configuration;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $repository
     */
    public function __construct(\Context $context, ApiConfigurationInterface $apiConfiguration, GeneralConfigurationInterface $configuration, BasketSessionRepositoryInterface $repository)
    {
        $this->context = $context;
        $this->apiConfiguration = $apiConfiguration;
        $this->configuration = $configuration;
        $this->repository = $repository;
    }

    public function getAssets(): Assets
    {
        $fetchAfterRender = $this->shouldFetchBindingKeyAfterPageRender();
        $bindingApiKey = $fetchAfterRender ? null : $this->getBindingApiKey();

        return (new Assets())
            ->addJavaScriptVariable('inpostizi_fetch_binding_key', $fetchAfterRender)
            ->addJavaScriptVariable('inpostizi_merchant_client_id', $this->apiConfiguration->getMerchantClientId())
            ->addJavaScriptVariable('inpostizi_widget_split_bound_enabled', $this->configuration->isWidgetSplitBoundEnabled())
            ->addJavaScriptVariable('inpostizi_binding_api_key', $bindingApiKey);
    }

    private function getBindingApiKey(): ?string
    {
        $cartId = (int) $this->context->cart->id;

        if (null === $session = $this->repository->findByEntityId($cartId)) {
            return null;
        }

        return $session->getBindingApiKey();
    }

    private function shouldFetchBindingKeyAfterPageRender(): bool
    {
        if (!$this->context->controller instanceof \ProductControllerCore) {
            return false;
        }

        return $this->configuration->isFullPageCacheModuleInUse();
    }
}
