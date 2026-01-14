<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Currency;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\Basket\CreateCartCommand;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\CurrencyRepository;

final class CreateCartHandler implements CreateCartHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    public function __construct(\Context $context, PrestaShopConfiguration $configuration, ObjectManagerInterface $manager)
    {
        $this->context = $context;
        $this->configuration = $configuration;
        $this->manager = $manager;
    }

    public function __invoke(CreateCartCommand $command): Cart
    {
        if (null === $shopId = $command->getShopId()) {
            $shop = $this->context->shop;
        } elseif (null === $shop = $this->manager->getRepository(\Shop::class)->find($shopId)) {
            throw new \DomainException(\sprintf('Shop #%d does not exist.', $shopId));
        }

        $cart = new \Cart();
        $cart->id_shop = (int) $shop->id;
        $cart->id_shop_group = (int) $shop->id_shop_group;
        $cart->id_lang = $this->configuration->getDefaultLanguageId($shopId);
        $cart->id_currency = (int) $this->getCurrency()->id;

        $this->manager->save($cart);

        return new Cart($cart);
    }

    private function getCurrency(): \Currency
    {
        /** @var CurrencyRepository $repository */
        $repository = $this->manager->getRepository(\Currency::class);
        $currency = $repository->findOneByIsoCode($isoCode = Currency::getDefault()->value);

        if (null === $currency) {
            throw new \RuntimeException(\sprintf('Currency "%s" does not exist.', $isoCode));
        }

        return $currency;
    }
}
