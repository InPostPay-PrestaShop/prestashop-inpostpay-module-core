<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

use izi\prestashop\Database\Connection;

/**
 * @template-implements DiscountRepositoryInterface<CartRuleDiscount>
 */
final class CartRuleDiscountRepository implements DiscountRepositoryInterface
{
    public const TABLE_NAME = 'inpostizi_inpost_discount';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var \ReflectionClass<CartRuleDiscount>
     */
    private $reflection;

    /**
     * @var array<int, array<int, CartRuleDiscount|null>> discount by cart and cart rule IDs
     */
    private $discounts = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->reflection = new \ReflectionClass(CartRuleDiscount::class);
    }

    /**
     * @param CartRuleDiscount $discount
     */
    public function add(DiscountInterface $discount): void
    {
        $this->assertIsSupportedClass($discount);
        if (null !== $discount->getId()) {
            throw new \DomainException('Discount has already been persisted.');
        }

        $this->connection->insert(self::TABLE_NAME, [
            'cart_id' => $cartId = $discount->getCartId(),
            'type' => $discount->getType(),
            'net_amount' => $discount->getAmount()->getNet(),
            'gross_amount' => $discount->getAmount()->getGross(),
            'cart_rule_id' => $cartRuleId = $discount->getCartRuleId(),
        ]);

        $this->setPropertyValue($discount, 'id', (int) $this->connection->getLastInsertId());
        $this->discounts[$cartId][$cartRuleId] = $discount;
    }

    /**
     * @param CartRuleDiscount $discount
     */
    public function remove(DiscountInterface $discount): void
    {
        $this->assertIsSupportedClass($discount);
        if (null === $id = $discount->getId()) {
            throw new \DomainException('Cannot remove a discount that has not been persisted.');
        }

        $this->connection->delete(self::TABLE_NAME, ['id' => $id]);
        unset($this->discounts[$discount->getCartId()][$discount->getCartRuleId()]);
    }

    public function findByCartId(int $cartId): array
    {
        $qb = (new \DbQuery())
            ->from(self::TABLE_NAME)
            ->where('cart_id = ' . $cartId);

        if ([] === $data = $this->connection->fetchAllAssociative((string) $qb)) {
            return [];
        }

        return \array_map([$this, 'hydrate'], $data);
    }

    public function findOneByCartAndRuleId(int $cartId, int $cartRuleId): ?CartRuleDiscount
    {
        if (\array_key_exists($cartId, $this->discounts) && \array_key_exists($cartRuleId, $this->discounts[$cartId])) {
            return $this->discounts[$cartId][$cartRuleId];
        }

        $qb = (new \DbQuery())
            ->from(self::TABLE_NAME)
            ->where('cart_id = ' . $cartId)
            ->where('cart_rule_id = ' . $cartRuleId);

        if (false === $row = $this->connection->fetchAssociative((string) $qb)) {
            return $this->discounts[$cartId][$cartRuleId] = null;
        }

        return $this->discounts[$cartId][$cartRuleId] = $this->hydrate($row);
    }

    private function assertIsSupportedClass(DiscountInterface $discount): void
    {
        if ($discount instanceof CartRuleDiscount) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf('Expected $discount to be an instance of "%s", "%s" given.', CartRuleDiscount::class, \get_class($discount)));
    }

    private function hydrate(array $row): CartRuleDiscount
    {
        $amount = new DiscountAmount((float) $row['net_amount'], (float) $row['gross_amount']);

        $discount = new CartRuleDiscount((int) $row['cart_id'], $row['type'], $amount, (int) $row['cart_rule_id']);
        $this->setPropertyValue($discount, 'id', (int) $row['id']);

        return $discount;
    }

    /**
     * @param string $name property name
     * @param mixed $value property value
     */
    private function setPropertyValue(CartRuleDiscount $discount, string $name, $value): void
    {
        $property = $this->reflection->getProperty($name);
        if (80100 > \PHP_VERSION_ID) {
            $property->setAccessible(true);
        }
        $property->setValue($discount, $value);
    }
}
