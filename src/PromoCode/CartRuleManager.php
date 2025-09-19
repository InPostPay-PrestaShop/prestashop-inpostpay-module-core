<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\PromoCode\Exception\CouldNotAddPromoCodeException;
use izi\prestashop\PromoCode\Exception\CouldNotRemovePromoCodeException;
use izi\prestashop\PromoCode\Exception\InvalidPromoCodeException;
use izi\prestashop\PromoCode\Exception\PromoCodeNotFoundException;

final class CartRuleManager implements PromoCodeManagerInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    public function __construct(\Context $context, ObjectManagerInterface $manager)
    {
        $this->context = $context;
        $this->manager = $manager;
    }

    /**
     * @return CartRulePromoCode[]
     */
    public function getPromoCodes(\Cart $cart): array
    {
        return array_map(static function (array $cartRule): CartRulePromoCode {
            return new CartRulePromoCode($cartRule['obj']);
        }, $cart->getCartRules(\CartRule::FILTER_ACTION_ALL, false));
    }

    public function addPromoCode(\Cart $cart, string $code): void
    {
        if (!\Validate::isCleanHtml($code)) {
            throw new InvalidPromoCodeException();
        }

        $cartRule = $this->manager->getRepository(\CartRule::class)->findOneBy([
            'code' => $code,
            'id_lang' => (int) $cart->id_lang,
        ]);

        if (null === $cartRule) {
            throw new PromoCodeNotFoundException();
        }

        $context = $this->context->cloneContext();
        $context->cart = $cart;

        if ($error = $cartRule->checkValidity($context)) {
            throw new InvalidPromoCodeException($error);
        }

        try {
            $result = $cart->addCartRule($cartRule->id);
        } catch (\Exception $e) {
            throw CouldNotAddPromoCodeException::create($e);
        }

        if (!$result) {
            throw CouldNotAddPromoCodeException::create();
        }
    }

    /**
     * @param CartRulePromoCode $promoCode
     */
    public function removePromoCode(\Cart $cart, PromoCodeInterface $promoCode): void
    {
        if (!$promoCode instanceof CartRulePromoCode) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of "%s", "%s" given.', CartRulePromoCode::class, get_debug_type($promoCode)));
        }

        $cartRuleId = (int) $promoCode->getCartRule()->id;

        try {
            $result = $cart->removeCartRule($cartRuleId);
        } catch (\Exception $e) {
            throw CouldNotRemovePromoCodeException::create($e);
        }

        if (!$result) {
            throw CouldNotRemovePromoCodeException::create();
        }
    }
}
