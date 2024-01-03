<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\item\BasketNotice;

/**
 * @template T of object
 */
interface BasketBuilderInterface
{
    /**
     * @return T
     */
    public function build();

    /**
     * @return static
     */
    public function setExpirationDate(?\DateTimeImmutable $expirationDate): self;

    /**
     * @return static
     */
    public function setNotice(?BasketNotice $notice): self;

    /**
     * @return static
     */
    public function setAdditionalInformation(?string $info): self;
}
