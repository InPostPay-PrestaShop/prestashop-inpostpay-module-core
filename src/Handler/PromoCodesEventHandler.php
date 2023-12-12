<?php

namespace izi\prestashop\Handler;

use izi\item\BasketNotice;

final class PromoCodesEventHandler implements BasketEventHandlerInterface
{
    public const EVENT_TYPE = 'PROMO_CODES';
    private const TRANSLATION_SOURCE = 'promocodeseventhandler';

    private $context;
    private $translator;
    private $module;

    public function __construct(\Context $context, \Module $module = null)
    {
        $this->context = $context;
        $this->translator = $context->getTranslator();
        $this->module = $module ?? \Module::getInstanceByName('inpostizi');
    }

    public function handle(\Cart $cart, $event): ?BasketNotice
    {
        if (self::EVENT_TYPE !== $event->event_type) {
            throw new \InvalidArgumentException(sprintf('Unsupported event type "%s".', $event->event_type));
        }

        $cartRules = $cart->getCartRules(\CartRule::FILTER_ACTION_ALL, false);
        $currentCodes = array_map(static function (array $cartRule): string {
            return $cartRule['code'] ?: $cartRule['name'];
        }, $cartRules);

        $appliedCodes = [];

        foreach ($event->promo_codes_event_data as $data) {
            $code = trim($data->promo_code_value);

            if (
                !in_array($code, $currentCodes, true) &&
                null !== $error = $this->addCartRule($cart, $code)
            ) {
                return BasketNotice::error($error);
            }

            $appliedCodes[] = $code;
        }

        foreach ($cartRules as $cartRule) {
            if ('' === $cartRule['code']) {
                continue;
            }

            if (!in_array($cartRule['code'], $appliedCodes, true)) {
                $cart->removeCartRule($cartRule['id_cart_rule']);
            }
        }

        return BasketNotice::attention($this->module->l('Voucher has been activated.', self::TRANSLATION_SOURCE));
    }

    private function addCartRule(\Cart $cart, string $code): ?string
    {
        if ('' === $code) {
            return $this->translator->trans('You must enter a voucher code.', [], 'Shop.Notifications.Error');
        }

        if (!\Validate::isCleanHtml($code)) {
            return $this->translator->trans('The voucher code is invalid.', [], 'Shop.Notifications.Error');
        }

        $cartRule = new \CartRule(\CartRule::getIdByCode($code));
        if (!\Validate::isLoadedObject($cartRule)) {
            return $this->translator->trans('This voucher does not exist.', [], 'Shop.Notifications.Error');
        }

        $context = $this->context->cloneContext();
        $context->cart = $cart;

        if ($error = $cartRule->checkValidity($context)) {
            return $error;
        }

        if (!$cart->addCartRule($cartRule->id)) {
            throw new \RuntimeException(sprintf('Could not add promo code "%s" to cart #%d.', $code, $cart->id));
        }

        \izi\prestashop\Logger::log(sprintf('Applied promo code "%s" to cart #%d.', $code, $cart->id));

        return null;
    }
}
