<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\BasketApp\Basket\Response\UpsertBasketResponse;
use izi\prestashop\Common\PhoneNumber;

final class BindingConfirmation implements \JsonSerializable, \Stringable
{
    /**
     * @var BindingStatus
     */
    private $status;

    /**
     * @var string|null
     */
    private $inpost_basket_id;

    /**
     * @var PhoneNumber|null
     */
    private $phone_number;

    /**
     * @var Browser|null
     */
    private $browser;

    /**
     * @var string|null
     */
    private $masked_phone_number;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $surname;

    public function __construct(BindingStatus $status, ?string $inpost_basket_id = null, ?PhoneNumber $phone_number = null, ?Browser $browser = null, ?string $masked_phone_number = null, ?string $name = null, ?string $surname = null)
    {
        $this->status = $status;
        $this->inpost_basket_id = $inpost_basket_id;
        $this->phone_number = $phone_number;
        $this->browser = $browser;
        $this->masked_phone_number = $masked_phone_number;
        $this->name = $name;
        $this->surname = $surname;
    }

    public static function fromLinkedBasketBindingResponse(BasketBindingResponse $binding, ?string $browserId = null): self
    {
        if (!$binding->isBasketLinked()) {
            throw new \DomainException('Basket is not linked.');
        }

        return self::success($binding, $browserId);
    }

    public static function fromTrustedBrowserUpsertBasketResponse(string $browserId, BasketBindingResponse $binding, UpsertBasketResponse $response): self
    {
        if (!$binding->isBrowserTrusted()) {
            throw new \DomainException('Browser is not trusted.');
        }

        return self::success($binding, $browserId, $response->getInPostBasketId());
    }

    public function getStatus(): BindingStatus
    {
        return $this->status;
    }

    public function getInPostBasketId(): ?string
    {
        return $this->inpost_basket_id;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function getBrowser(): ?Browser
    {
        return $this->browser;
    }

    public function getMaskedPhoneNumber(): ?string
    {
        return $this->masked_phone_number;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function __toString(): string
    {
        return json_encode($this);
    }

    private static function success(BasketBindingResponse $binding, ?string $browserId = null, ?string $inPostBasketId = null): self
    {
        if (null === $clientDetails = $binding->getClientDetails()) {
            throw new \UnexpectedValueException('Client details are missing in the binding data.');
        }

        $browser = new Browser($binding->isBrowserTrusted(), $browserId);

        return new self(
            BindingStatus::Success(),
            $inPostBasketId ?? $binding->getInPostBasketId(),
            $clientDetails->getPhoneNumber(),
            $browser,
            $clientDetails->getMaskedPhoneNumber(),
            $clientDetails->getName(),
            $clientDetails->getSurname()
        );
    }
}
