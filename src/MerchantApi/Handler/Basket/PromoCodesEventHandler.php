<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use izi\prestashop\PromoCode\Exception\CouldNotAddPromoCodeException;
use izi\prestashop\PromoCode\Exception\CouldNotRemovePromoCodeException;
use izi\prestashop\PromoCode\Exception\InvalidPromoCodeException;
use izi\prestashop\PromoCode\Exception\PromoCodeExceptionInterface;
use izi\prestashop\PromoCode\Exception\PromoCodeNotFoundException;
use izi\prestashop\PromoCode\PromoCodeInterface;
use izi\prestashop\PromoCode\PromoCodeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PromoCodesEventHandler implements BasketEventHandlerInterface
{
    /**
     * @var PromoCodeManagerInterface
     */
    private $manager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PromoCodeManagerInterface $manager, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public static function getHandledEventType(): string
    {
        return EventType::PromoCodes()->value;
    }

    public function handle(BasketInterface $basket, BasketEvent $event): ?Notice
    {
        if (EventType::PromoCodes() !== $type = $event->getType()) {
            throw new \DomainException(\sprintf('Unsupported event type "%s".', $type->value));
        }

        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(\sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, \get_class($cart)));
        }

        $notice = null;

        $promoCodes = $this->manager->getPromoCodes($cart);
        $currentCodes = array_map(static function (PromoCodeInterface $promoCode): string {
            return $promoCode->getCode();
        }, $promoCodes);

        $appliedCodes = [];

        foreach ($event->getPromoCodesEventData() as $promoCode) {
            $code = trim($promoCode->getCode());

            if (\in_array($code, $currentCodes, true)) {
                $appliedCodes[] = $code;

                continue;
            }

            if (null !== $error = $this->addPromoCode($cart, $code)) {
                return Notice::error($error);
            }

            $appliedCodes[] = $code;
            $notice = $notice ?? Notice::attention($this->translator->trans('Voucher has been activated.', [], 'Modules.Inpostizi.Notifications'));
        }

        foreach ($promoCodes as $promoCode) {
            if ('' === $code = $promoCode->getCode()) {
                continue;
            }

            if (\in_array($code, $appliedCodes, true)) {
                continue;
            }

            if (null !== $error = $this->removePromoCode($cart, $promoCode)) {
                return Notice::error($error);
            }

            $notice = $notice ?? Notice::attention($this->translator->trans('Voucher has been removed.', [], 'Modules.Inpostizi.Notifications'));
        }

        return $notice;
    }

    /**
     * @return string|null error message or null on success
     */
    private function addPromoCode(\Cart $cart, string $code): ?string
    {
        if ('' === $code) {
            return $this->translator->trans('You must enter a voucher code.', [], 'Shop.Notifications.Error');
        }

        try {
            $this->manager->addPromoCode($cart, $code);
        } catch (PromoCodeNotFoundException $e) {
            return $this->translator->trans('This voucher does not exist.', [], 'Shop.Notifications.Error');
        } catch (InvalidPromoCodeException $e) {
            return $e->getMessage() ?: $this->translator->trans('The voucher code is invalid.', [], 'Shop.Notifications.Error');
        } catch (CouldNotAddPromoCodeException $e) {
            $this->logger->error('Could not add promo code "{code}" to cart #{cartId}.', [
                'code' => $code,
                'cartId' => $cart->id,
                'exception' => $e->getPrevious(),
            ]);

            return $this->translator->trans('Could not add the voucher to your cart.', [], 'Modules.Inpostizi.Errors');
        } catch (PromoCodeExceptionInterface $e) {
            return $e->getMessage();
        }

        $this->logger->info('Applied promo code "{code}" to cart #{cartId}.', [
            'code' => $code,
            'cartId' => $cart->id,
        ]);

        return null;
    }

    /**
     * @return string|null error message or null on success
     */
    private function removePromoCode(\Cart $cart, PromoCodeInterface $promoCode): ?string
    {
        try {
            $this->manager->removePromoCode($cart, $promoCode);
        } catch (CouldNotRemovePromoCodeException $e) {
            $this->logger->error('Could not remove code "{code}" from cart #{cartId}.', [
                'code' => $promoCode->getCode(),
                'cartId' => $cart->id,
                'exception' => $e->getPrevious(),
            ]);

            return $this->translator->trans('Could not remove the voucher from your cart.', [], 'Modules.Inpostizi.Errors');
        } catch (PromoCodeExceptionInterface $e) {
            return $e->getMessage();
        }

        return null;
    }
}
