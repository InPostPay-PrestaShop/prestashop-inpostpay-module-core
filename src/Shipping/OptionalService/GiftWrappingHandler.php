<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Shipping\OptionalService\Exception\ServiceUnavailableException;
use izi\prestashop\Translation\LegacyTranslator;

final class GiftWrappingHandler implements OptionalServiceHandlerInterface
{
    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(PrestaShopConfiguration $configuration, ObjectManagerInterface $manager, LegacyTranslator $translator)
    {
        $this->configuration = $configuration;
        $this->manager = $manager;
        $this->translator = $translator;
    }

    public function supports(string $serviceCode): bool
    {
        return 'GW' === $serviceCode;
    }

    public function handle(\Cart $cart, string $serviceCode, DeliveryType $deliveryType, bool $selected): void
    {
        if ('GW' !== $serviceCode) {
            throw new \DomainException(sprintf('Unsupported service "%s".', $serviceCode));
        }

        if ($selected && !$this->configuration->isGiftWrappingEnabled()) {
            throw new ServiceUnavailableException($serviceCode, $this->translator->l('Gift wrapping is no longer available.', 'giftwrappinghandler'));
        }

        if ($selected === (bool) $cart->gift) {
            return;
        }

        $cart->gift = $selected;
        if (!$selected) {
            $cart->gift_message = '';
        }

        $this->manager->save($cart);
    }
}
