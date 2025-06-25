<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\EventListener;

use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\BasketApp\Product\Exception\ProductNotFoundException;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\Currency;
use izi\prestashop\HotProduct\Exception\InvalidProductDataException;
use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\HotProductValidator;
use izi\prestashop\HotProduct\Message\DeleteRemoteProductCommand;
use izi\prestashop\HotProduct\Message\UpdateHotProductCommand;
use izi\prestashop\OAuth2\Exception\OAuth2ExceptionInterface;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Product\Event\CombinationEvent;
use izi\prestashop\Product\Event\ImageEvent;
use izi\prestashop\Product\Event\ProductEvent;
use izi\prestashop\Product\Event\SpecificPriceEvent;
use izi\prestashop\Product\Event\StockQuantityUpdatedEvent;
use izi\prestashop\Product\Price\PriceCalculatorInterface;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UpdateHotProductsListener implements EventSubscriberInterface
{
    private const OBSERVED_PRODUCT_PROPERTIES = [
        'active',
        'available_for_order',
        'customizable',
        'name',
        'price',
        'minimal_quantity',
        'description',
        'description_short',
        'ean13',
    ];

    private const OBSERVED_COMBINATION_PROPERTIES = [
        'price',
        'minimal_quantity',
        'ean13',
    ];

    /**
     * @var Context
     */
    private $context;

    /**
     * @var HotProductRepositoryInterface
     */
    private $repository;

    /**
     * @var PriceCalculatorInterface
     */
    private $calculator;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HotProductValidator
     */
    private $validator;

    /**
     * @var array<int, HotProduct[]> hot products by product ID
     */
    private $productsMap;

    /**
     * @var \Product|null product being deleted
     */
    private $product;

    /**
     * @var \Combination|null combination being deleted
     */
    private $combination;

    private $shutdownRegistered = false;

    /**
     * @var array<int, HotProduct>
     */
    private $toDelete = [];

    /**
     * @var array<int, HotProduct>
     */
    private $toUpdate = [];

    public function __construct(Context $context, HotProductRepositoryInterface $repository, PriceCalculatorInterface $calculator, CommandBusInterface $bus, LoggerInterface $logger, ?HotProductValidator $validator = null)
    {
        $this->context = $context;
        $this->repository = $repository;
        $this->calculator = $calculator;
        $this->bus = $bus;
        $this->logger = $logger;
        $this->validator = $validator ?? self::createValidator();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvent::DELETION => 'onProductDeletion',
            ProductEvent::DELETED => 'onProductDeleted',
            ProductEvent::UPDATED => 'onProductUpdated',
            CombinationEvent::DELETION => 'onCombinationDeletion',
            CombinationEvent::DELETED => 'onCombinationDeleted',
            CombinationEvent::UPDATED => 'onCombinationUpdated',
            ImageEvent::CREATED => 'onImagesUpdated',
            ImageEvent::DELETED => 'onImagesUpdated',
            SpecificPriceEvent::CREATED => 'onSpecificPricesUpdated',
            SpecificPriceEvent::UPDATED => 'onSpecificPricesUpdated',
            SpecificPriceEvent::DELETED => 'onSpecificPricesUpdated',
            StockQuantityUpdatedEvent::class => 'onStockQuantityUpdate',
        ];
    }

    public function onProductDeletion(ProductEvent $event): void
    {
        $product = $event->getProduct();

        if ([] === $this->getHotProducts((int) $product->id)) {
            return;
        }

        $this->product = $product; // keep an object reference to check after deletion
    }

    public function onProductDeleted(ProductEvent $event): void
    {
        if ($this->product !== $event->getProduct()) {
            return;
        }

        foreach ($this->getHotProducts((int) $this->product->id) as $hotProduct) {
            if (!$this->wasUpdatedInShop($this->product, $hotProduct->getShopId())) {
                continue;
            }

            $this->scheduleDelete($hotProduct);
        }

        $this->product = null;
    }

    public function onProductUpdated(ProductEvent $event): void
    {
        $product = $event->getProduct();
        $updatedFields = $this->getUpdatedFields($product);

        if (null !== $updatedFields && [] === array_intersect(array_keys($updatedFields), self::OBSERVED_PRODUCT_PROPERTIES)) {
            return;
        }

        if ([] === $hotProducts = $this->getHotProducts((int) $product->id)) {
            return;
        }

        foreach ($hotProducts as $hotProduct) {
            if (array_key_exists($hotProduct->getId(), $this->toDelete)) {
                return;
            }

            if (!$this->wasUpdatedInShop($product, $hotProduct->getShopId())) {
                continue;
            }

            $this->scheduleUpdate($hotProduct);
        }
    }

    public function onCombinationDeletion(CombinationEvent $event): void
    {
        $combination = $event->getCombination();
        $combinationId = (int) $this->combination->id;

        foreach ($this->getHotProducts((int) $combination->id_product) as $hotProduct) {
            if ($hotProduct->getCombinationId() !== $combinationId) {
                continue;
            }

            $this->combination = $combination; // keep an object reference to check after deletion

            return;
        }
    }

    public function onCombinationDeleted(CombinationEvent $event): void
    {
        if ($this->combination !== $event->getCombination()) {
            return;
        }

        $combinationId = (int) $this->combination->id;

        foreach ($this->getHotProducts((int) $this->combination->id_product) as $hotProduct) {
            if ($hotProduct->getCombinationId() !== $combinationId) {
                continue;
            }

            if (!$this->wasUpdatedInShop($this->combination, $hotProduct->getShopId())) {
                continue;
            }

            $this->scheduleDelete($hotProduct);
        }

        $this->combination = null;
    }

    public function onCombinationUpdated(CombinationEvent $event): void
    {
        $combination = $event->getCombination();
        $updatedFields = $this->getUpdatedFields($combination);

        if (null !== $updatedFields && [] === array_intersect(array_keys($updatedFields), self::OBSERVED_COMBINATION_PROPERTIES)) {
            return;
        }

        $productId = (int) $combination->id_product;
        $combinationId = (int) $combination->id;

        foreach ($this->getHotProducts($productId) as $hotProduct) {
            if (array_key_exists($hotProduct->getId(), $this->toDelete)) {
                return;
            }

            if ($hotProduct->getCombinationId() !== $combinationId) {
                continue;
            }

            if (!$this->wasUpdatedInShop($combination, $hotProduct->getShopId())) {
                continue;
            }

            $this->scheduleUpdate($hotProduct);
        }
    }

    public function onImagesUpdated(ImageEvent $event): void
    {
        $image = $event->getImage();
        $productId = (int) $image->id_product;

        foreach ($this->getHotProducts($productId) as $hotProduct) {
            if (array_key_exists($hotProduct->getId(), $this->toDelete)) {
                return;
            }

            if (!$this->wasUpdatedInShop($image, $hotProduct->getShopId())) {
                continue;
            }

            $this->scheduleUpdate($hotProduct);
        }
    }

    public function onSpecificPricesUpdated(SpecificPriceEvent $event): void
    {
        $price = $event->getPrice();

        if ($price->id_cart || $price->id_customer) {
            return;
        }

        $productId = (int) $price->id_product;

        foreach ($this->getHotProducts($productId) as $hotProduct) {
            if (array_key_exists($hotProduct->getId(), $this->toDelete)) {
                return;
            }

            if (!$this->doesAffectProduct($hotProduct, $price)) {
                continue;
            }

            $this->scheduleUpdate($hotProduct);
        }
    }

    public function onStockQuantityUpdate(StockQuantityUpdatedEvent $event): void
    {
        if (0 === $event->getDeltaQuantity()) {
            return;
        }

        $combinationId = $event->getCombinationId();
        $updatedShopId = $event->getShopId();
        $shopGroup = is_callable([$this->context, 'getContextShopGroup'])
            ? $this->context->getContextShopGroup()
            : \Shop::getContextShopGroup();

        foreach ($this->getHotProducts($event->getProductId()) as $hotProduct) {
            if ((int) $hotProduct->getCombinationId() !== $combinationId) {
                continue;
            }

            $shopId = $hotProduct->getShopId();

            if (!$shopGroup->share_stock && null !== $shopId && $updatedShopId !== $shopId) {
                continue;
            }

            $this->scheduleUpdate($hotProduct);
        }
    }

    private function scheduleDelete(HotProduct $product): void
    {
        $this->registerShutdownFunction();
        $this->toDelete[$product->getId()] = $product;
        unset($this->toUpdate[$product->getId()]);
    }

    private function scheduleUpdate(HotProduct $product): void
    {
        $this->registerShutdownFunction();
        $this->toUpdate[$product->getId()] = $product;
    }

    private function registerShutdownFunction(): void
    {
        if ($this->shutdownRegistered) {
            return;
        }

        register_shutdown_function(function () {
            try {
                $this->processUpdates();
            } finally {
                $this->toUpdate = $this->toDelete = [];
            }
        });

        $this->shutdownRegistered = true;
    }

    private function processUpdates(): void
    {
        foreach ($this->toUpdate as $id => $product) {
            try {
                $this->validator->validate($product);
            } catch (InvalidProductDataException $e) {
                $this->toDelete[$id] = $product;
                unset($this->toUpdate[$id]);
            } catch (\Throwable $e) {
                // ignore and attempt to update anyway
            }
        }

        foreach ($this->toDelete as $product) {
            try {
                $this->bus->handle(new DeleteRemoteProductCommand((string) $product->getReferenceId()));
            } catch (BasketAppException|NetworkExceptionInterface|OAuth2ExceptionInterface $e) {
                $this->logger->error('Failed to delete hot product "{id}".', [
                    'id' => (string) $product->getReferenceId(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to delete hot product "{id}". Error: {exception}', [
                    'id' => (string) $product->getReferenceId(),
                    'exception' => $e,
                ]);
            }
        }

        foreach ($this->toUpdate as $product) {
            try {
                $this->bus->handle(new UpdateHotProductCommand($product->getId()));
            } catch (ProductNotFoundException $e) {
                // ignore
            } catch (BasketAppException|NetworkExceptionInterface|OAuth2ExceptionInterface $e) {
                $this->logger->error('Failed to update hot product "{id}" data.', [
                    'id' => (string) $product->getReferenceId(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update hot product "{id}" data. Error: {exception}', [
                    'id' => (string) $product->getReferenceId(),
                    'exception' => $e,
                ]);
            }
        }
    }

    private function getHotProducts(int $productId): array
    {
        if (!isset($this->productsMap)) {
            $this->loadHotProductData();
        }

        return $this->productsMap[$productId] ?? [];
    }

    private function loadHotProductData(): void
    {
        $this->productsMap = [];

        foreach ($this->repository->findAll() as $product) {
            $this->productsMap[$product->getProductId()][] = $product;
        }
    }

    private function wasUpdatedInShop(\ObjectModel $model, ?int $shopId): bool
    {
        if (null === $shopId) {
            return true;
        }

        $shopIds = $model->id_shop_list ?: $this->context->getContextListShopID();

        return in_array($shopId, $shopIds, false);
    }

    private function doesAffectProduct(HotProduct $product, \SpecificPrice $price): bool
    {
        $combinationId = (int) $price->id_product_attribute;

        if (0 !== $combinationId && $product->getCombinationId() !== $combinationId) {
            return false;
        }

        $shopId = (int) $price->id_shop;
        $currencyId = (int) $price->id_currency;
        $countryId = (int) $price->id_country;
        $customerGroupId = (int) $price->id_group;

        if (0 === $shopId && 0 === $currencyId && 0 === $countryId && 0 === $customerGroupId) {
            return true;
        }

        $productShopId = $product->getShopId();

        if (0 !== $shopId && null !== $productShopId && $productShopId !== $shopId) {
            return false;
        }

        $parameters = $this->calculator->getCalculationParameters(Currency::getDefault(), $productShopId);

        if (0 !== $currencyId && $currencyId !== $parameters->getCurrencyId()) {
            return false;
        }

        if (0 !== $countryId && $countryId !== $parameters->getCountryId()) {
            return false;
        }

        if (0 !== $customerGroupId && $customerGroupId !== $parameters->getCustomerGroupId()) {
            return false;
        }

        return true;
    }

    private function getUpdatedFields(\ObjectModel $model): ?array
    {
        if (is_callable([$model, 'getFieldsToUpdate'])) {
            return $model->getFieldsToUpdate();
        }

        return (\Closure::bind(function () {
            return $this->update_fields;
        }, $model, \ObjectModel::class))();
    }

    private static function createValidator(): HotProductValidator
    {
        @trigger_error(sprintf('Not passing a $validator to "%s::__construct()" is deprecated since version 2.2.2.', __CLASS__), E_USER_DEPRECATED);

        /** @var \InPostIzi $module */
        $module = \Module::getInstanceByName('inpostizi');
        $repository = $module->get(ObjectManagerInterface::class)->getRepository(\Product::class);

        return new HotProductValidator($repository);
    }
}
