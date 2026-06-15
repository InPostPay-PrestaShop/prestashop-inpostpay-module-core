<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\EventListener;

use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\TerminateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UpdateBasketListener implements EventSubscriberInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<int, int>
     */
    private $updatedCartIds = [];

    public function __construct(ApiConfigurationInterface $configuration, CommandBusInterface $bus, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->bus = $bus;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => 'onCartUpdated',
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        if (0 >= $cartId = (int) $event->getCart()->id) {
            return;
        }

        if ([] === $this->updatedCartIds) {
            $dispatcher->addListener(TerminateEvent::class, [$this, 'onTerminate']);
        }

        $this->updatedCartIds[$cartId] = $cartId;
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if ([] === $this->updatedCartIds) {
            return;
        }

        /* @see https://github.com/PrestaShop/PrestaShop/pull/28267 \Shop instatiation on PS 1.7.8 might result in an error when attempting to read/write from a file given by a relative path */
        if (\Tools::version_compare(_PS_VERSION_, '1.7.8', '>=') && \Tools::version_compare(_PS_VERSION_, '8.0.0')) {
            \Configuration::set('_PS_CACHE_DIR_', _PS_CACHE_DIR_);
        }

        try {
            $this->updateBaskets();
        } finally {
            $this->updatedCartIds = [];
        }
    }

    private function updateBaskets(): void
    {
        foreach ($this->updatedCartIds as $cartId) {
            try {
                $command = new UpdateBasketCommand($cartId);

                $this->bus->handle($command);
            } catch (\Throwable $e) {
                $this->logger->critical('Could not send updated cart #{cartId} data.', [
                    'cartId' => $cartId,
                    'exception' => $e,
                ]);
            }
        }
    }
}
