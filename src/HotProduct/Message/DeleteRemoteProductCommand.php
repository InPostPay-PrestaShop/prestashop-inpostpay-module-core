<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\Message;

use izi\prestashop\HotProduct\MessageHandler\DeleteRemoteProductHandler;

/**
 * @see DeleteRemoteProductHandler
 */
final class DeleteRemoteProductCommand
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int|null
     */
    private $shopId;

    public function __construct(string $id, ?int $shopId = null)
    {
        $this->id = $id;
        $this->shopId = $shopId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }
}
