<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Shipping\OptionalService\Exception\ServiceUnavailableException;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(PrestaShopConfiguration $configuration, ObjectManagerInterface $manager, TranslatorInterface $translator)
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
            throw new \DomainException(\sprintf('Unsupported service "%s".', $serviceCode));
        }

        if ($selected && !$this->configuration->isGiftWrappingEnabled()) {
            throw new ServiceUnavailableException($serviceCode, $this->translator->trans('Gift wrapping is no longer available.', [], 'Modules.Inpostizi.Errors'));
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
