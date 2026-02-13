<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\Basket\AvailablePromotion;
use izi\prestashop\Common\Basket\PromoDetails;
use izi\prestashop\Common\Basket\PromotionType;
use izi\prestashop\Configuration\PromoCodesConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\CartRuleRepository;
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
     * @var CartRuleRepository
     */
    private $cartRuleRepository;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @param ObjectRepositoryInterface<\CMS> $cmsRepository
     * @param CartRuleRepository $cartRuleRepository
     */
    public function __construct(CartRuleOptionsRepositoryInterface $repository, PromoCodesConfigurationInterface $configuration, ObjectRepositoryInterface $cmsRepository, ObjectRepositoryInterface $cartRuleRepository, \Context $context)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->cmsRepository = $cmsRepository;
        $this->cartRuleRepository = $cartRuleRepository;
        $this->context = $context;
    }

    public function getAvailablePromotions(\Cart $cart): array
    {
        if ([] === $cartRules = $this->getAvailableHighlightedCartRules($cart)) {
            return [];
        }

        $promotions = [];

        foreach ($cartRules as $cartRule) {
            if (null === $promotion = $this->mapPromotionData($cart, $cartRule)) {
                continue;
            }

            $promotions[] = $promotion;

            if (count($promotions) >= self::MAX_PROMO_COUNT) {
                break;
            }
        }

        return $promotions;
    }

    private function mapPromotionData(\Cart $cart, array $cartRule): ?AvailablePromotion
    {
        if (null === $details = $this->getPromoDetails($cart, (int) $cartRule['id_cart_rule'])) {
            return null;
        }

        $description = trim(\Tools::substr($cartRule['name'], 0, 60));
        $startDate = $this->parseDateTime($cartRule['date_from'] ?? null);
        $endDate = $this->parseDateTime($cartRule['date_to'] ?? null);
        $priority = isset($cartRule['priority']) ? (int) $cartRule['priority'] : null;

        return new AvailablePromotion(
            PromotionType::Merchant(),
            (string) $cartRule['code'],
            $description,
            $details,
            $startDate,
            $endDate,
            $priority
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

        $cartRules = array_filter($discounts, function ($discount) use ($cart, $cartRuleIdsToSkip) {
            if ('' === (string) $discount['code']) {
                return false;
            }

            if (!empty($discount['carrier_restriction'])) {
                // discount cannot be added unless a delivery option is selected via checkout page,
                // and before finalizing the order we do not know what delivery option will actually be used
                return false;
            }

            $cartRuleId = (int) $discount['id_cart_rule'];

            if (!empty($discount['country_restriction']) && !$this->canCountryRestrictedCartRuleBeAdded($cart, $cartRuleId)) {
                return false;
            }

            return !in_array($cartRuleId, $cartRuleIdsToSkip, true);
        });

        usort($cartRules, static function (array $a, array $b): int {
            $priorityA = $a['priority'] ?? \PHP_INT_MAX;
            $priorityB = $b['priority'] ?? \PHP_INT_MAX;

            if (0 !== $result = $priorityA <=> $priorityB) {
                return $result;
            }

            return $b['id_cart_rule'] <=> $a['id_cart_rule'];
        });

        return $cartRules;
    }

    private function getPromoDetails(\Cart $cart, int $cartRuleId): ?PromoDetails
    {
        $shopId = (int) $this->context->shop->id;

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

    private function canCountryRestrictedCartRuleBeAdded(\Cart $cart, int $cartRuleId): bool
    {
        if (0 >= $addressId = (int) $cart->id_address_delivery) {
            // discount cannot be added unless the delivery address is selected
            return false;
        }

        foreach ($this->cartRuleRepository->getCompatibleCountries($cartRuleId) as $country) {
            if ('PL' !== $country->iso_code) {
                // as of writing this comment, only domestic delivery is available
                continue;
            }

            /** @var \Address[] $addresses */
            $addresses = $cart->getAddressCollection();
            $address = $addresses[$addressId];

            return (int) $address->id_country === (int) $country->id;
        }

        return false;
    }
}
