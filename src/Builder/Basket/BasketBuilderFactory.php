<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\Basket\BasketAppRequestBuilderInterface as RequestBuilder;
use izi\prestashop\Builder\Basket\MerchantApiResponseBuilderInterface as ResponseBuilder;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\ContextManager;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Module\ModuleRepository;
use izi\prestashop\Product\Price\LowestPriceProviderFactory;
use izi\prestashop\Product\Price\LowestPriceProviderInterface;
use izi\prestashop\PromoCode\CartRulePromoCodeProvider;
use izi\prestashop\PromoCode\PromoCodeProviderInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\NullLogger;

final class BasketBuilderFactory implements BasketBuilderFactoryInterface
{
    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var ContextManager
     */
    private $contextManager;

    /**
     * @var ConsentsConfigurationInterface
     */
    private $consentsConfiguration;

    /**
     * @var ProductConfigurationInterface
     */
    private $productConfiguration;

    /**
     * @var DeliveryFactory
     */
    private $deliveryFactory;

    /**
     * @var ProductDeliveryFactory
     */
    private $deliveryRelatedProductFactory;

    /**
     * @var LowestPriceProviderInterface
     */
    private $lowestPriceProvider;

    /**
     * @var PromoCodeProviderInterface
     */
    private $promoCodeProvider;

    public function __construct(
        ClockInterface $clock,
        ContextManager $contextManager,
        ConsentsConfigurationInterface $consentsConfiguration,
        ProductConfigurationInterface $productConfiguration,
        DeliveryFactory $deliveryFactory,
        ProductDeliveryFactory $deliveryRelatedProductFactory,
        ?LowestPriceProviderInterface $lowestPriceProvider = null,
        ?PromoCodeProviderInterface $promoCodeProvider = null
    ) {
        $this->clock = $clock;
        $this->contextManager = $contextManager;
        $this->consentsConfiguration = $consentsConfiguration;
        $this->productConfiguration = $productConfiguration;
        $this->deliveryFactory = $deliveryFactory;
        $this->deliveryRelatedProductFactory = $deliveryRelatedProductFactory;
        $this->lowestPriceProvider = $lowestPriceProvider ?? $this->createLowestPriceProvider();
        $this->promoCodeProvider = $promoCodeProvider ?? CartRulePromoCodeProvider::create();
    }

    public function createRequestBuilder(BasketInterface $basket): RequestBuilder
    {
        $cart = $this->getCart($basket);

        $builder = new BasketAppRequestBuilder(
            $cart,
            $this->contextManager,
            $this->consentsConfiguration,
            $this->productConfiguration,
            $this->deliveryFactory,
            $this->deliveryRelatedProductFactory,
            null,
            $this->lowestPriceProvider,
            $this->promoCodeProvider
        );

        return $builder->setExpirationDate($this->getExpirationDate());
    }

    public function createResponseBuilder(BasketInterface $basket): ResponseBuilder
    {
        $cart = $this->getCart($basket);

        $builder = new MerchantApiResponseBuilder(
            $cart,
            $this->contextManager,
            $this->consentsConfiguration,
            $this->productConfiguration,
            $this->deliveryFactory,
            $this->deliveryRelatedProductFactory,
            null,
            $this->lowestPriceProvider,
            $this->promoCodeProvider
        );

        return $builder->setExpirationDate($this->getExpirationDate());
    }

    private function getCart(BasketInterface $basket): \Cart
    {
        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, get_class($cart)));
        }

        return $cart;
    }

    // TODO configurable?
    private function getExpirationDate(): \DateTimeImmutable
    {
        return $this->clock->now()->add(new \DateInterval('P2D'));
    }

    private function createLowestPriceProvider(): LowestPriceProviderInterface
    {
        $repository = new ModuleRepository();

        $module = $repository->findByName('inpostizi');
        $logger = $module ? $module->getLogger() : new NullLogger();

        return (new LowestPriceProviderFactory($repository, $logger))->create();
    }
}
