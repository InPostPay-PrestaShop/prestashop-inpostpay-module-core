<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\EventListener;

use izi\prestashop\Analytics\Command\UpdateCartAnalyticsCommand;
use izi\prestashop\Analytics\Cookie\CookieEraserInterface;
use izi\prestashop\Analytics\Factory\BasketAnalyticsFactoryInterface;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Event\CartUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class UpdateBasketAnalyticsListener implements EventSubscriberInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var BasketAnalyticsFactoryInterface
     */
    private $basketAnalyticsFactory;

    /**
     * @var CookieEraserInterface
     */
    private $cookieEraser;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    public function __construct(
        CommandBusInterface $commandBus,
        RequestStack $requestStack,
        BasketAnalyticsFactoryInterface $basketAnalyticsFactory,
        CookieEraserInterface $cookieEraser,
        GeneralConfigurationInterface $generalConfiguration
    ) {
        $this->commandBus = $commandBus;
        $this->requestStack = $requestStack;
        $this->basketAnalyticsFactory = $basketAnalyticsFactory;
        $this->cookieEraser = $cookieEraser;
        $this->generalConfiguration = $generalConfiguration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => 'onCartUpdated',
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->generalConfiguration->isSendAnalyticsData()) {
            return;
        }

        $basketAnalytics = $this->basketAnalyticsFactory->createFromRequest($request);

        if ($basketAnalytics->isEmpty()) {
            return;
        }

        $this->commandBus->handle(new UpdateCartAnalyticsCommand((int) $event->getCart()->id, $basketAnalytics));
        $this->cookieEraser->erase($request);
    }
}
