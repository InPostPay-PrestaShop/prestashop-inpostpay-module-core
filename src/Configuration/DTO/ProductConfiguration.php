<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;


use izi\prestashop\Configuration\ProductConfigurationInterface;

final class ProductConfiguration implements ProductConfigurationInterface
{
    /**
     * @var int|null
     */
    private $normalImageTypeId;

    /**
     * @var int|null
     */
    private $smallImageTypeId;

    /**
     * @var int|null
     */
    private $largeImageTypeId;

    public function __construct(
        ?int $normalImageTypeId,
        ?int $smallImageTypeId,
        ?int $largeImageTypeId
    ) {
        $this->normalImageTypeId = $normalImageTypeId;
        $this->smallImageTypeId = $smallImageTypeId;
        $this->largeImageTypeId = $largeImageTypeId;
    }

    public function getNormalImageTypeId(?int $shopId = null): ?int
    {
        return $this->normalImageTypeId;
    }

    public function setNormalImageTypeId(?\ImageType $imageType): void
    {
        $this->normalImageTypeId = $imageType ? $imageType->id : null;
    }

    public function getSmallImageTypeId(?int $shopId = null): ?int
    {
        return $this->smallImageTypeId;
    }

    public function setSmallImageTypeId(?\ImageType $smallImageType): void
    {
        $this->smallImageTypeId = $smallImageType ? $smallImageType->id : null;
    }

    public function getLargeImageTypeId(?int $shopId = null): ?int
    {
        return $this->largeImageTypeId;
    }

    public function setLargeImageTypeId(?\ImageType $largeImageType): void
    {
        $this->largeImageTypeId = $largeImageType ? $largeImageType->id : null;
    }
}
