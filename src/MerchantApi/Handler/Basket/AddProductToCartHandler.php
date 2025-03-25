<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\Basket\AddProductToCartCommand;
use izi\prestashop\MerchantApi\Exception\CannotAddProductException;
use izi\prestashop\MerchantApi\Exception\ProductAlreadyInCartException;
use izi\prestashop\MerchantApi\Exception\ProductNotFoundException;
use izi\prestashop\MerchantApi\Exception\ProductOutOfStockException;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Translation\LegacyTranslator;
use Psr\Log\LoggerInterface;

final class AddProductToCartHandler implements AddProductToCartHandlerInterface
{
    use CommandHandlerTrait;

    private const TRANSLATION_SOURCE = 'addproducttocarthandler';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ObjectRepositoryInterface
     */
    private $combinationRepository;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductRepository $productRepository
     * @param ObjectRepositoryInterface<\Combination> $combinationRepository
     */
    public function __construct(\Context $context, ObjectRepositoryInterface $productRepository, ObjectRepositoryInterface $combinationRepository, LegacyTranslator $translator, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->productRepository = $productRepository;
        $this->combinationRepository = $combinationRepository;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public function __invoke(AddProductToCartCommand $command): void
    {
        $quantity = $command->getQuantity();

        if (null !== $quantity && 0 >= $quantity) {
            throw new CannotAddProductException($this->context->getTranslator()->trans('Null quantity.', [], 'Shop.Notifications.Error'));
        }

        if (!\Validate::isLoadedObject($cart = $command->getCart())) {
            throw new \DomainException('Cart does not exist.');
        }

        $productId = $command->getProductId();

        if (null === $product = $this->productRepository->find($productId, (int) $cart->id_lang)) {
            throw ProductNotFoundException::create();
        }

        if (!$product->active || !$product->available_for_order || !$product->checkAccess((int) $cart->id_customer)) {
            throw CannotAddProductException::create($this->context->getTranslator()->trans('This product (%product%) is no longer available.', [
                '%product%' => $product->name,
            ], 'Shop.Notifications.Error'));
        }

        if (2 === (int) $product->customizable) {
            throw CannotAddProductException::create($this->translator->l('This product requires customization. Please add it to your cart via the shop page.', RelatedProductsEventHandler::TRANSLATION_SOURCE));
        }

        $combinationId = $command->getCombinationId();
        $hasCombinations = $product->hasCombinations();

        if (!$hasCombinations && null !== $combinationId) {
            throw ProductNotFoundException::create();
        }

        if ($hasCombinations && 0 === (int) $combinationId) {
            $combinationId = $this->productRepository->getDefaultCombinationId($productId);
        }

        $combinationId = (int) $combinationId;

        if ($this->isInCart($cart, $productId, $combinationId)) {
            throw ProductAlreadyInCartException::create($this->translator->l('This product is already in your cart.', self::TRANSLATION_SOURCE));
        }

        $minimalQuantity = $this->getMinimalQuantity($product, $combinationId);
        $quantity = $quantity ?? $minimalQuantity;

        if ($quantity < $minimalQuantity) {
            throw CannotAddProductException::create($this->context->getTranslator()->trans('The minimum purchase order quantity for the product %product% is %quantity%.', [
                '%product%' => $product->name,
                '%quantity%' => $minimalQuantity,
            ], 'Shop.Notifications.Error'));
        }

        $this->assertQuantityIsAvailable($quantity, $productId, $combinationId, (int) $cart->id_shop);
        $this->addToCart($cart, $productId, $combinationId, $quantity);
    }

    private function addToCart(\Cart $cart, int $productId, int $combinationId, int $quantity): void
    {
        try {
            $result = $cart->updateQty(
                $quantity,
                $productId,
                $combinationId,
                0,
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

        $this->logger->critical('Could not add product [{productId}] to cart #{cartId}.', [
            'productId' => implode('-', [$productId, $combinationId]),
            'cartId' => $cart->id,
        ]);

        throw $e ?? new CannotAddProductException($this->translator->l('Could not add the product to your cart.', RelatedProductsEventHandler::TRANSLATION_SOURCE));
    }

    private function getMinimalQuantity(\Product $product, int $combinationId): int
    {
        if (0 === $combinationId) {
            return (int) $product->minimal_quantity;
        }

        $combination = $this->combinationRepository->find($combinationId);

        if (null === $combination || (int) $product->id !== (int) $combination->id_product) {
            throw ProductNotFoundException::create();
        }

        return (int) $combination->minimal_quantity;
    }

    private function assertQuantityIsAvailable(int $quantity, int $productId, int $combinationId, int $shopId): void
    {
        if ($this->productRepository->isAvailableOutOfStock($productId)) {
            return;
        }

        $availableQuantity = $this->productRepository->getAvailableStockQuantity($productId, $combinationId, $shopId);

        if ($quantity > $availableQuantity) {
            throw ProductOutOfStockException::create(max($availableQuantity, 0));
        }
    }

    private function isInCart(\Cart $cart, int $productId, int $combinationId): bool
    {
        foreach ($cart->getProducts() as $cartProduct) {
            if ($this->isSameProduct($cartProduct, $productId, $combinationId)) {
                return true;
            }
        }

        return false;
    }

    private function isSameProduct(array $cartProduct, int $productId, int $combinationId): bool
    {
        return $productId === (int) $cartProduct['id_product']
            && $combinationId === (int) $cartProduct['id_product_attribute']
            && 0 === (int) $cartProduct['id_customization'];
    }
}
