<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Util;

use izi\prestashop\InPostDiscount\CartRule\CustomRuleRepositoryInterface;
use izi\prestashop\InPostDiscount\CartRule\Factory\AbstractCartRuleFactory;
use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @internal
 *
 * @TODO: refactor into protected {@see AbstractCartRuleFactory} methods in next major.
 */
final class CartRuleFactoryHelper
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomRuleRepositoryInterface
     */
    private $customRuleRepository;

    public function __construct(ObjectManagerInterface $objectManager, CustomRuleRepositoryInterface $customRuleRepository)
    {
        $this->objectManager = $objectManager;
        $this->customRuleRepository = $customRuleRepository;
    }

    public function create(string $type, string $name): \CartRule
    {
        $cartRule = new \CartRule();

        $cartRule->name = [];
        foreach ($this->objectManager->getRepository(\Language::class)->findAll() as $language) {
            $cartRule->name[$language->id] = $name;
        }

        $cartRule->date_from = '2000-01-01 00:00:00'; // arbitrary past date
        $cartRule->date_to = '2050-01-01 00:00:00'; // arbitrary future date
        $cartRule->description = $type;
        $cartRule->partial_use = false;
        $cartRule->reduction_tax = true;
        $cartRule->highlight = false;
        $cartRule->active = true;

        return $cartRule;
    }

    /**
     * @param string $type discount type
     * @param callable(\CartRule): void $configure
     * @param string|null $key the repository key, also used as the cart rule code; defaults to the discount type
     */
    public function findOrCreateCustom(string $type, string $name, callable $configure, ?string $key = null): \CartRule
    {
        $key = $key ?? $type;
        $cartRuleId = $this->customRuleRepository->getCartRuleId($key);

        if (null === $cartRuleId || null === $cartRule = $this->objectManager->find(\CartRule::class, $cartRuleId)) {
            $cartRule = $this->createCustom($key, $type, $name);
            $configure($cartRule);
            $this->persistCustom($cartRule, $key);

            return $cartRule;
        }

        if (!$cartRule->active) {
            $cartRule->active = true;
            $this->objectManager->save($cartRule);
        }

        return $cartRule;
    }

    private function createCustom(string $code, string $type, string $name): \CartRule
    {
        $cartRule = $this->create($type, $name);
        $cartRule->code = $code;

        $metadata = $this->objectManager->getMetadata(\CartRule::class);
        if (!empty($metadata['fields']['quantity']['allow_null'])) {
            $cartRule->quantity = $cartRule->quantity_per_user = null; // unlimited quantity
        } else {
            $cartRule->quantity = $cartRule->quantity_per_user = 2 ** 32 - 1; // max unsigned 32-bit int
        }

        return $cartRule;
    }

    private function persistCustom(\CartRule $cartRule, string $key): void
    {
        try {
            $this->objectManager->save($cartRule);
            $this->customRuleRepository->registerCartRule($key, (int) $cartRule->id);
        } catch (\Throwable $e) {
            try {
                $this->objectManager->remove($cartRule);
            } catch (\Throwable $ignored) {
                // ignore and rethrow the original exception
            }

            throw $e;
        }
    }
}
