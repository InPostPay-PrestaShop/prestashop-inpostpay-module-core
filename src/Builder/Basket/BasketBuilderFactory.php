<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\Basket\BasketAppRequestBuilderInterface as RequestBuilder;
use izi\prestashop\Builder\Basket\MerchantApiResponseBuilderInterface as ResponseBuilder;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\ContextManager;
use izi\prestashop\Entities\BasketInterface;
use Psr\Clock\ClockInterface;

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
     * @var DeliveryFactory
     */
    private $deliveryFactory;

    public function __construct(ClockInterface $clock, ContextManager $contextManager, ConsentsConfigurationInterface $consentsConfiguration, DeliveryFactory $deliveryFactory)
    {
        $this->clock = $clock;
        $this->contextManager = $contextManager;
        $this->consentsConfiguration = $consentsConfiguration;
        $this->deliveryFactory = $deliveryFactory;
    }

    public function createRequestBuilder(BasketInterface $basket): RequestBuilder
    {
        $cart = $this->getCart($basket);

        $builder = new BasketAppRequestBuilder(
            $cart,
            $this->contextManager,
            $this->consentsConfiguration,
            $this->deliveryFactory
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
            $this->deliveryFactory
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
}
