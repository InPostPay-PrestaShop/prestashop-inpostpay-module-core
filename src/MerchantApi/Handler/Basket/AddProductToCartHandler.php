<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Cart\Exception\ProductAlreadyInCartException;
use izi\prestashop\Cart\Util\ProductHelper;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\Basket\AddProductToCartCommand;
use izi\prestashop\MerchantApi\Exception\CannotAddProductException;
use izi\prestashop\MerchantApi\Exception\ProductNotFoundException;
use izi\prestashop\MerchantApi\Exception\ProductOutOfStockException;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Translation\LegacyTranslator;
use Psr\Log\LoggerInterface;

/* IGNORE_THIS_FILE_FOR_TRANSLATION */
final class AddProductToCartHandler implements AddProductToCartHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ProductRepository
     */
    private $productRepository;

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
     */
    public function __construct(\Context $context, ObjectRepositoryInterface $productRepository, LegacyTranslator $translator, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->productRepository = $productRepository;
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
        $combinationId = $command->getCombinationId();

        $productWithCombination = $this->productRepository->findWithCombination(
            $productId,
            $combinationId,
            (int) $cart->id_lang,
            null,
            true
        );

        if (null === $productWithCombination) {
            throw ProductNotFoundException::create();
        }

        $product = $productWithCombination->getProduct();
        $combination = $productWithCombination->getCombination();
        $combinationId = $combination ? (int) $combination->id : 0;

        if (!$product->active || !$product->available_for_order || !$product->checkAccess((int) $cart->id_customer)) {
            throw CannotAddProductException::create($this->context->getTranslator()->trans('This product (%product%) is no longer available.', ['%product%' => $product->name], 'Shop.Notifications.Error'));
        }

        if (2 === (int) $product->customizable) {
            throw CannotAddProductException::create($this->translator->l('This product requires customization. Please add it to your cart via the shop page.', RelatedProductsEventHandler::TRANSLATION_SOURCE));
        }

        if ($cartProduct = ProductHelper::findProductInCart($cart, $productId, $combinationId)) {
            throw new ProductAlreadyInCartException($cartProduct);
        }

        $minimalQuantity = $combination ? (int) $combination->minimal_quantity : (int) $product->minimal_quantity;
        $quantity = $quantity ?? $minimalQuantity;

        if ($quantity < $minimalQuantity) {
            throw CannotAddProductException::create($this->context->getTranslator()->trans('The minimum purchase order quantity for the product %product% is %quantity%.', ['%product%' => $product->name, '%quantity%' => $minimalQuantity], 'Shop.Notifications.Error'));
        }

        $this->assertQuantityIsAvailable($quantity, $productId, $combinationId, $cart);
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
