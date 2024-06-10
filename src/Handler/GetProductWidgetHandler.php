<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use izi\prestashop\Command\GetProductWidgetCommand;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Handler\Result\ProductWidgetResult;
use izi\prestashop\Hook\Front\ProductWidgetRendererTrait;

final class GetProductWidgetHandler implements GetProductWidgetHandlerInterface
{
    use ProductWidgetRendererTrait;

    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    /**
     * @var WidgetInterface
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(
        GuiConfigurationInterface $configuration,
        GeneralConfigurationInterface $generalConfiguration,
        WidgetInterface $module,
        \Context $context,
        ProductRepository $productRepository
    ) {
        $this->configuration = $configuration;
        $this->generalConfiguration = $generalConfiguration;
        $this->module = $module;
        $this->context = $context;
        $this->productRepository = $productRepository;
    }

    public static function getHandledCommandClass(): string
    {
        return GetProductWidgetCommand::class;
    }

    public function __invoke(GetProductWidgetCommand $command): ProductWidgetResult
    {
        if (!$this->productExists($command->getProductId())) {
            throw new \DomainException(sprintf('Product with id: "%s" does not exist', $command->getProductId()));
        }

        $product = $this->getProduct($command->getProductId());
        $parameters = ['product' => $product];
        $widget = $this->renderWidget($product, $parameters, $command->getHookName());

        return new ProductWidgetResult($widget);
    }

    /**
     * @return array|ProductLazyArray
     */
    private function getProduct(int $productId)
    {
        $presentFactory = $this->getPresenterFactory();
        $productAssembler = new \ProductAssembler($this->context);

        return $presentFactory->getPresenter()->present(
            $presentFactory->getPresentationSettings(),
            $productAssembler->assembleProduct([
                'id_product' => $productId,
                'id_product_attribute' => 0,
            ]),
            $this->context->language
        );
    }


    private function getPresenterFactory(): \ProductPresenterFactory
    {
        $presenterFactory = new \ProductPresenterFactory($this->context);

        return $presenterFactory;
    }

    private function productExists(int $idProduct): bool
    {
        return $this->productRepository->productExists($idProduct);
    }
}
