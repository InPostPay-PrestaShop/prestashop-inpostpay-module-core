<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\Basket\AvailablePromotion;
use izi\prestashop\Common\Basket\PromoDetails;
use izi\prestashop\Common\Basket\PromotionType;
use izi\prestashop\Configuration\PromoCodesConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class AvailableCartRulesProvider implements AvailablePromotionsProviderInterface
{
    private const NULL_DATE = '0000-00-00 00:00:00';

    /**
     * @var CartRuleOptionsRepositoryInterface
     */
    private $repository;

    /**
     * @var PromoCodesConfigurationInterface
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface<\CMS>
     */
    private $cmsRepository;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @param ObjectRepositoryInterface<\CMS> $cmsRepository
     */
    public function __construct(CartRuleOptionsRepositoryInterface $repository, PromoCodesConfigurationInterface $configuration, ObjectRepositoryInterface $cmsRepository, \Context $context)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->cmsRepository = $cmsRepository;
        $this->context = $context;
    }

    public function getAvailablePromotions(\Cart $cart): array
    {
        if ([] === $cartRules = $this->getAvailableHighlightedCartRules($cart)) {
            return [];
        }

        return array_filter(array_map(function (array $cartRule) use ($cart) {
            return $this->mapPromotionData($cart, $cartRule);
        }, $cartRules));
    }

    private function mapPromotionData(\Cart $cart, array $cartRule): ?AvailablePromotion
    {
        if (null === $details = $this->getPromoDetails($cart, (int) $cartRule['id_cart_rule'])) {
            return null;
        }

        $description = trim(\Tools::substr($cartRule['name'], 0, 60));
        $startDate = $this->parseDateTime($cartRule['date_from']);
        $endDate = $this->parseDateTime($cartRule['date_to']);

        return new AvailablePromotion(
            PromotionType::Merchant(),
            (string) $cartRule['code'],
            $description,
            $details,
            $startDate,
            $endDate,
            (int) $cartRule['priority']
        );
    }

    private function getAvailableHighlightedCartRules(\Cart $cart): array
    {
        if ([] === $discounts = $cart->getDiscounts()) {
            return [];
        }

        $cartRuleIdsToSkip = array_map(static function (array $cartRule): int {
            return (int) $cartRule['id_cart_rule'];
        }, $cart->getCartRules(\CartRule::FILTER_ACTION_ALL, false));

        return array_filter($discounts, static function ($discount) use ($cartRuleIdsToSkip) {
            if ('' === (string) $discount['code']) {
                return false;
            }

            return !in_array((int) $discount['id_cart_rule'], $cartRuleIdsToSkip, true);
        });
    }

    private function getPromoDetails(\Cart $cart, int $cartRuleId): ?PromoDetails
    {
        $shopId = (int) $cart->id_shop;

        if (null === $pageId = $this->getPromoDetailsPageId($cartRuleId, $shopId)) {
            return null;
        }

        $languageId = (int) $cart->id_lang;
        $cms = $this->cmsRepository->find($pageId, $languageId, $shopId);

        if (null === $cms || !$cms->active) {
            return null;
        }

        $url = $this->context->link->getCMSLink($cms, null, null, $languageId, $shopId);

        return new PromoDetails($url);
    }

    private function parseDateTime(?string $dateTimeString): ?\DateTimeImmutable
    {
        if (null === $dateTimeString || self::NULL_DATE === $dateTimeString) {
            return null;
        }

        return new \DateTimeImmutable($dateTimeString);
    }

    private function getPromoDetailsPageId(int $cartRuleId, int $shopId): ?int
    {
        $options = $this->repository->find($cartRuleId);

        if (null !== $options && null !== $pageId = $options->getPromoDetailsPageId()) {
            return $pageId;
        }

        return $this->configuration->getDefaultPromoDetailsPageId($shopId);
    }
}
