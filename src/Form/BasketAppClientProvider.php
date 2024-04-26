<?php

declare(strict_types=1);

namespace izi\prestashop\Form;

use izi\prestashop\BasketApp\BasketAppClientFactory;
use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\BasketApp\Payment\PaymentsApiClientInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Form\Event\ApiConfigurationValidatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BasketAppClientProvider implements EventSubscriberInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var BasketAppClientFactory
     */
    private $clientFactory;

    /**
     * @var PaymentsApiClientInterface&BasketAppClientInterface&null
     */
    private $client;

    /**
     * @param BasketAppClientInterface&PaymentsApiClientInterface&null $client
     */
    public function __construct(ApiConfigurationInterface $configuration, BasketAppClientFactory $clientFactory, ?BasketAppClientInterface $client = null)
    {
        $this->configuration = $configuration;
        $this->clientFactory = $clientFactory;
        $this->client = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ApiConfigurationValidatedEvent::class => 'onApiConfigurationValidated',
        ];
    }

    /**
     * @return BasketAppClientInterface&PaymentsApiClientInterface|null
     */
    public function getClient(): ?BasketAppClientInterface
    {
        if (null === $this->configuration->getClientCredentials()) {
            return null;
        }

        return $this->client ?? ($this->client = $this->clientFactory->create($this->configuration));
    }

    public function onApiConfigurationValidated(ApiConfigurationValidatedEvent $event): void
    {
        $newConfiguration = $event->getConfiguration();

        if ($this->doesConfigurationChange($newConfiguration)) {
            $this->client = null;
        }

        $this->configuration = $newConfiguration;
    }

    private function doesConfigurationChange(ApiConfigurationInterface $newConfiguration): bool
    {
        return $this->configuration->getClientCredentials() !== $newConfiguration->getClientCredentials()
            || $this->configuration->getEnvironment() !== $newConfiguration->getEnvironment();
    }
}
