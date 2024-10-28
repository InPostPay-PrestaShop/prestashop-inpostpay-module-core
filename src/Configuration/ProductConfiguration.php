<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

/**
 * @implements PersistentConfigurationInterface<ProductConfigurationInterface>
 */
final class ProductConfiguration implements ProductConfigurationInterface, PersistentConfigurationInterface
{
    private const INPOST_PAY_PRODUCT_IMAGE_NORMAL_TYPE = 'INPOST_PAY_PRODUCT_IMAGE_NORMAL_TYPE';
    private const INPOST_PAY_PRODUCT_IMAGE_SMALL_TYPE = 'INPOST_PAY_PRODUCT_IMAGE_SMALL_TYPE';
    private const INPOST_PAY_PRODUCT_IMAGE_LARGE_TYPE = 'INPOST_PAY_PRODUCT_IMAGE_LARGE_TYPE';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    public function __construct(ShopAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getNormalImageTypeId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::INPOST_PAY_PRODUCT_IMAGE_NORMAL_TYPE, $shopId);
    }

    public function getSmallImageTypeId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::INPOST_PAY_PRODUCT_IMAGE_SMALL_TYPE, $shopId);
    }

    public function getLargeImageTypeId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::INPOST_PAY_PRODUCT_IMAGE_LARGE_TYPE, $shopId);
    }

    public function copy(): ProductConfigurationInterface
    {
        return new DTO\ProductConfiguration(
            $this->getNormalImageTypeId(),
            $this->getSmallImageTypeId(),
            $this->getLargeImageTypeId()
        );
    }

    public function persist(ProductConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::INPOST_PAY_PRODUCT_IMAGE_NORMAL_TYPE, $configuration->getNormalImageTypeId());
        $this->configuration->set(self::INPOST_PAY_PRODUCT_IMAGE_SMALL_TYPE, $configuration->getSmallImageTypeId());
        $this->configuration->set(self::INPOST_PAY_PRODUCT_IMAGE_LARGE_TYPE, $configuration->getLargeImageTypeId());
    }
}
