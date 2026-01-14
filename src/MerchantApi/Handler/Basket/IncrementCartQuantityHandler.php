<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Cart\Util\ProductHelper;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\Basket\IncrementCartQuantityCommand;
use izi\prestashop\MerchantApi\Exception\CannotAddProductException;
use izi\prestashop\MerchantApi\Exception\ProductOutOfStockException;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IncrementCartQuantityHandler implements IncrementCartQuantityHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(ObjectRepositoryInterface $productRepository, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->productRepository = $productRepository;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public function __invoke(IncrementCartQuantityCommand $command): int
    {
        $quantity = $command->getQuantity();

        if (0 >= $quantity) {
            throw new \DomainException('Quantity must be greater than zero.');
        }

        if (!\Validate::isLoadedObject($cart = $command->getCart())) {
            throw new \DomainException('Cart does not exist.');
        }

        $productId = $command->getProductId();
        $combinationId = $command->getCombinationId();
        $customizationId = $command->getCustomizationId();

        if (0 === $currentQuantity = ProductHelper::getCartQuantity($cart, $productId, $combinationId, $customizationId)) {
            throw new \DomainException('Product is not in cart.');
        }

        $this->assertQuantityIsAvailable($quantity, $productId, $combinationId, $cart);
        $this->updateCartQuantity($cart, $productId, $combinationId, $customizationId, $quantity);

        return $currentQuantity + $quantity;
    }

    private function updateCartQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId, int $quantity): void
    {
        try {
            $result = $cart->updateQty(
                $quantity,
                $productId,
                $combinationId,
                $customizationId,
                'up',
                0,
                null,
                false
            );
        } catch (\Exception $e) {
            $result = false;
        }

        if (false !== $result) {
            return;
        }

        $this->logger->critical('Could not increment [{productId}] quantity for cart #{cartId}.', [
            'productId' => implode('-', [$productId, $combinationId, $customizationId]),
            'cartId' => $cart->id,
        ]);

        throw $e ?? new CannotAddProductException($this->translator->trans('Could not add the product to your cart.', [], 'Modules.Inpostizi.Errors'));
    }

    private function assertQuantityIsAvailable(int $quantity, int $productId, int $combinationId, \Cart $cart): void
    {
        if ($this->productRepository->isAvailableOutOfStock($productId)) {
            return;
        }

        $availableQuantity = $this->productRepository->getAvailableQuantity($productId, $combinationId, $cart);

        if ($quantity > $availableQuantity) {
            throw ProductOutOfStockException::create(max($availableQuantity, 0));
        }
    }
}
