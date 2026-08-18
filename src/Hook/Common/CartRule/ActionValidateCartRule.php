<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\CartRule;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\InPostDiscount\CartRule\CustomRuleRepositoryInterface;
use izi\prestashop\InPostDiscount\CartRuleDiscountRepository;
use izi\prestashop\InPostDiscount\DiscountRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActionValidateCartRule implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionValidateCartRule';

    /**
     * @var CustomRuleRepositoryInterface
     */
    private $customRuleRepository;

    /**
     * @var CartRuleDiscountRepository
     */
    private $discountRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param CartRuleDiscountRepository $discountRepository
     */
    public function __construct(CustomRuleRepositoryInterface $customRuleRepository, DiscountRepositoryInterface $discountRepository, TranslatorInterface $translator)
    {
        $this->customRuleRepository = $customRuleRepository;
        $this->discountRepository = $discountRepository;
        $this->translator = $translator;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange('9.0.1');
    }

    /**
     * @param array{cart_rule: \CartRule, cart: \Cart, isValidatedByModules: bool|null, request?: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        [$cartRule, $cart, $validationResult, $request] = $this->extractParameters($parameters);

        if (null !== $validationResult) {
            return; // already handled by another module
        }

        if (!$this->customRuleRepository->isCustomCartRule((int) $cartRuleId = $cartRule->id)) {
            return;
        }

        $discount = $this->discountRepository->findOneByCartAndRuleId((int) $cart->id, $cartRuleId);

        if (
            null !== $discount
            && null !== $request
            && 'inpost_izi_merchant_api_order_create' === $request->attributes->get('_inpost_izi_route')
        ) {
            $parameters['isValidatedByModules'] = true;

            return;
        }

        $parameters['isValidatedByModules'] = false;
        $parameters['isValidatedByModulesError'] = $this->translator->trans('This voucher does not exist.', [], 'Shop.Notifications.Error');

        if (null !== $discount) {
            $this->discountRepository->remove($discount);
        }
    }

    /**
     * @param array{cart_rule: \CartRule, cart: \Cart, isValidatedByModules: bool|null, request?: Request} $parameters
     *
     * @return array{0: \CartRule, 1: \Cart, 2: bool|null, 3: Request|null}
     */
    private function extractParameters(array $parameters): array
    {
        $cartRule = $parameters['cart_rule'] ?? null;
        if (!$cartRule instanceof \CartRule) {
            throw InvalidHookParamException::unexpectedType('cart_rule', $cartRule, \CartRule::class);
        }

        $cart = $parameters['cart'] ?? null;
        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('cart', $cart, \Cart::class);
        }

        $request = $parameters['request'] ?? null;
        if (!$request instanceof Request) {
            $request = null;
        }

        return [$cartRule, $cart, $parameters['isValidatedByModules'] ?? null, $request];
    }
}
