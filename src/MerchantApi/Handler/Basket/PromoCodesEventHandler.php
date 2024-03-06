<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

final class PromoCodesEventHandler implements BasketEventHandlerInterface
{
    private const TRANSLATION_SOURCE = 'promocodeseventhandler';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\Module $module, \Context $context, ObjectManagerInterface $manager, LoggerInterface $logger)
    {
        $this->module = $module;
        $this->context = $context;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public static function getHandledEventType(): string
    {
        return EventType::PromoCodes()->value;
    }

    public function handle(BasketInterface $basket, BasketEvent $event): ?Notice
    {
        if (EventType::PromoCodes() !== $type = $event->getType()) {
            throw new \DomainException(sprintf('Unsupported event type "%s".', $type->value));
        }

        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, get_class($cart)));
        }

        $cartRules = $cart->getCartRules(\CartRule::FILTER_ACTION_ALL, false);
        $currentCodes = array_map(static function (array $cartRule): string {
            return $cartRule['code'] ?: $cartRule['name'];
        }, $cartRules);

        $appliedCodes = [];

        foreach ($event->getPromoCodesEventData() as $promoCode) {
            $code = trim($promoCode->getCode());

            if (
                !in_array($code, $currentCodes, true)
                && null !== $error = $this->addCartRule($cart, $code)
            ) {
                return Notice::error($error);
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

        return Notice::attention($this->module->l('Voucher has been activated.', self::TRANSLATION_SOURCE));
    }

    private function addCartRule(\Cart $cart, string $code): ?string
    {
        if ('' === $code) {
            return $this->context->getTranslator()->trans('You must enter a voucher code.', [], 'Shop.Notifications.Error');
        }

        if (!\Validate::isCleanHtml($code)) {
            return $this->context->getTranslator()->trans('The voucher code is invalid.', [], 'Shop.Notifications.Error');
        }

        $cartRule = $this->manager->getRepository(\CartRule::class)->findOneBy([
            'code' => $code,
            'id_lang' => (int) $cart->id_lang,
        ]);

        if (null === $cartRule) {
            return $this->context->getTranslator()->trans('This voucher does not exist.', [], 'Shop.Notifications.Error');
        }

        $context = $this->context->cloneContext();
        $context->cart = $cart;

        if ($error = $cartRule->checkValidity($context)) {
            return $error;
        }

        try {
            $result = $cart->addCartRule($cartRule->id);
        } catch (\Exception $e) {
            $result = false;
            $this->logger->critical('Promo code addition error: {error}', [
                'error' => $e,
            ]);
        }

        if (!$result) {
            isset($e) || $this->logger->critical('Could not add promo code "{code}" to cart #{cartId}.', [
                'code' => $code,
                'cartId' => $cart->id,
            ]);

            return $this->module->l('Could not add the voucher to your cart.', self::TRANSLATION_SOURCE);
        }

        $this->logger->info('Applied promo code "{code}" to cart #{cartId}.', [
            'code' => $code,
            'cartId' => $cart->id,
        ]);

        return null;
    }
}
