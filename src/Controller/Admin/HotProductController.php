<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\BasketApp\Product\Exception\MaxProductLimitReachedException;
use izi\prestashop\BasketApp\Product\Response\Status;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\Initializer\ConfigurationInitializerInterface;
use izi\prestashop\HotProduct\Exception\HotProductExceptionInterface;
use izi\prestashop\HotProduct\Exception\HotProductExistsException;
use izi\prestashop\HotProduct\Form\CreateHotProductType;
use izi\prestashop\HotProduct\Form\UpdateHotProductType;
use izi\prestashop\HotProduct\HotProductRepositoryInterface;
use izi\prestashop\HotProduct\Message\CreateHotProductCommand;
use izi\prestashop\HotProduct\Message\DeleteHotProductCommand;
use izi\prestashop\HotProduct\Message\DeleteRemoteProductCommand;
use izi\prestashop\HotProduct\Message\ImportHotProductCommand;
use izi\prestashop\HotProduct\Message\UpdateHotProductCommand;
use izi\prestashop\HotProduct\View\HotProductViewDataFactory;
use izi\prestashop\Http\Exception\HttpExceptionInterface;
use izi\prestashop\OAuth2\Exception\OAuth2ExceptionInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShopBundle\Security\Voter\PageVoter;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="hot-products", defaults={"_inpost_izi_hot_product_page"=true})
 */
final class HotProductController extends AbstractConfigurationController
{
    /**
     * @internal
     */
    public const TRANSLATION_SOURCE = 'hotproductcontroller';

    /**
     * @var Context
     */
    private $shopContext;

    /**
     * @param iterable<ConfigurationInitializerInterface> $configInitializers
     */
    public function __construct(Context $shopContext, LegacyTranslator $translator, \Context $context, ApiConfigurationInterface $apiConfiguration, iterable $configInitializers, bool $debug = false)
    {
        parent::__construct($translator, $context, $configInitializers, $apiConfiguration, $debug);
        $this->shopContext = $shopContext;
    }

    /**
     * @Route(name="admin_inpost_izi_products_index", methods={"GET"})
     */
    public function index(Request $request, HotProductRepositoryInterface $repository, HotProductViewDataFactory $viewDataFactory): Response
    {
        $this->checkAccess();

        if (null === $this->apiConfiguration->getClientCredentials()) {
            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        if (null !== $shopId = $this->shopContext->getContextShopID()) {
            $shopId = (int) $shopId;
        }

        $products = $repository->findAll($shopId);
        $viewData = $viewDataFactory->createForProducts($products);

        if (!$statusAvailable = $viewData->isStatusAvailable()) {
            $this->addFlash('warning', $this->translator->l('Failed to fetch product statuses from the API.', self::TRANSLATION_SOURCE));
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/hot_products/index.html.twig', [
            'products' => $viewData->getProducts(),
            'is_status_available' => $statusAvailable,
            'is_multistore_context' => null === $shopId,
            'layoutTitle' => $this->translator->l('Hot products', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
        ]);
    }

    /**
     * @Route(path="/new", name="admin_inpost_izi_products_create", methods={"GET", "POST"})
     */
    public function create(Request $request, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        if (null === $this->apiConfiguration->getClientCredentials()) {
            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        if (null !== $shopId = $this->shopContext->getContextShopID()) {
            $shopId = (int) $shopId;
        } else {
            $this->addFlash('warning', $this->translator->l('You are creating a hot product in a multistore context. Product data associated with the default shop will be sent to the Basket App.', self::TRANSLATION_SOURCE));
        }

        $form = $this->createForm(CreateHotProductType::class, new CreateHotProductCommand($shopId));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $bus->handle($form->getData());
                $this->addFlash('success', $this->translator->l('Hot product has been created successfully and will be awaiting approval.', self::TRANSLATION_SOURCE));

                return $this->redirectToRoute('admin_inpost_izi_products_index');
            } catch (HotProductExistsException $e) {
                $this->addFlash('error', $this->translator->l('There already exists a hot product for product and combination.', self::TRANSLATION_SOURCE));
            } catch (\Throwable $e) {
                $this->handleUpdateError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/hot_products/create.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->l('New hot product', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
            'is_ps_176' => str_starts_with(_PS_VERSION_, '1.7.6'),
        ]);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_inpost_izi_products_edit", methods={"GET", "POST"}, requirements={"id"="\d+"})
     */
    public function edit(int $id, Request $request, CommandBusInterface $bus, HotProductRepositoryInterface $repository, ProductRepository $productRepository): Response
    {
        $this->checkAccess();

        if (null === $this->apiConfiguration->getClientCredentials()) {
            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        if (null === $product = $repository->find($id)) {
            $this->addFlash('error', $this->translator->l('Hot product was not found.', self::TRANSLATION_SOURCE));

            return $this->redirectToRoute('admin_inpost_izi_products_index');
        }

        $form = $this->createForm(UpdateHotProductType::class, UpdateHotProductCommand::for($product));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var Status $status */
                $status = $bus->handle($form->getData());
                if (Status::Active() === $status) {
                    $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));
                } else {
                    $this->addFlash('success', $this->translator->l('Hot product has been updated successfully and will be awaiting approval.', self::TRANSLATION_SOURCE));
                }

                return $this->redirectToRoute('admin_inpost_izi_products_index');
            } catch (\Throwable $e) {
                $this->handleUpdateError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/hot_products/edit.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => sprintf(
                $this->translator->l('Edit hot product: %s', self::TRANSLATION_SOURCE),
                $productRepository->getProductNameByProductId($product->getProductId(), (int) $this->context->language->id, $product->getCombinationId())
            ),
            'headerTabContent' => $this->renderNav($request),
        ]);
    }

    /**
     * @Route(path="/{id}/delete", name="admin_inpost_izi_products_delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(int $id, Request $request, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        if (null === $this->apiConfiguration->getClientCredentials()) {
            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        if (!$this->isCsrfTokenValid('inpost-izi-delete-product', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', $this->translator->l('The CSRF token is invalid.', self::TRANSLATION_SOURCE));

            return $this->redirectToRoute('admin_inpost_izi_products_index');
        }

        $command = new DeleteHotProductCommand($id);

        try {
            $bus->handle($command);
            $this->addFlash('success', $this->translator->l('Hot product has been deleted successfully.', self::TRANSLATION_SOURCE));
        } catch (\Throwable $e) {
            $this->handleUpdateError($e, $request);
        }

        return $this->redirectToRoute('admin_inpost_izi_products_index');
    }

    /**
     * @Route(path="/import/{referenceId}", name="admin_inpost_izi_products_api_import", methods={"POST"})
     */
    public function import(string $referenceId, Request $request, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        if (null === $this->apiConfiguration->getClientCredentials()) {
            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        if (!$this->isCsrfTokenValid('inpost-izi-import-product', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', $this->translator->l('The CSRF token is invalid.', self::TRANSLATION_SOURCE));

            return $this->redirectToRoute('admin_inpost_izi_products_index');
        }

        if (null !== $shopId = $this->shopContext->getContextShopID()) {
            $shopId = (int) $shopId;
        }

        try {
            $command = new ImportHotProductCommand($referenceId, $shopId);
            $bus->handle($command);

            $this->addFlash('success', $this->translator->l('Product has been imported successfully.', self::TRANSLATION_SOURCE));
        } catch (HotProductExistsException $e) {
            $this->addFlash('error', $this->translator->l('There already exists a hot product for product and combination.', self::TRANSLATION_SOURCE));
        } catch (\Throwable $e) {
            $this->handleUpdateError($e, $request);
        }

        return $this->redirectToRoute('admin_inpost_izi_products_index');
    }

    /**
     * @Route(path="/delete-remote/{referenceId}", name="admin_inpost_izi_products_api_delete", methods={"DELETE"})
     */
    public function deleteRemote(string $referenceId, Request $request, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        if (null === $this->apiConfiguration->getClientCredentials()) {
            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        if (!$this->isCsrfTokenValid('inpost-izi-delete-remote-product', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', $this->translator->l('The CSRF token is invalid.', self::TRANSLATION_SOURCE));

            return $this->redirectToRoute('admin_inpost_izi_products_index');
        }

        try {
            $command = new DeleteRemoteProductCommand($referenceId);
            $bus->handle($command);

            $this->addFlash('success', $this->translator->l('Product has been deleted from API successfully.', self::TRANSLATION_SOURCE));
        } catch (\Throwable $e) {
            $this->handleUpdateError($e, $request);
        }

        return $this->redirectToRoute('admin_inpost_izi_products_index');
    }

    /**
     * @internal
     *
     * @Route(path="/autocomplete", name="admin_inpost_izi_products_autocomplete", methods={"GET"})
     */
    public function autocomplete(Request $request, ProductRepository $repository): Response
    {
        $this->denyAccessUnlessGranted(...self::getProductsReadPermission());

        if ('' === $query = (string) $request->query->get('query')) {
            throw new UnprocessableEntityHttpException('Query cannot be empty.');
        }

        if (0 >= $page = $request->query->getInt('page', 1)) {
            throw new UnprocessableEntityHttpException('Page number must be greater than 0.');
        }

        $qb = $repository->createSearchQueryBuilder($query, (int) $this->context->language->id, (int) $this->context->shop->id);
        $countQb = clone $qb;

        $results = $qb
            ->limit(10, ($page - 1) * 10)
            ->build()
            ->getResult();

        $count = (int) $countQb
            ->setSelect('COUNT(DISTINCT p.id_product)')
            ->build()
            ->getSingleScalarResult();

        if ($page < ceil($count / 10)) {
            $parameters = array_merge($request->attributes->get('_route_params'), $request->query->all(), ['page' => $page + 1]);
            $nextPage = $this->generateUrl($request->attributes->get('_route'), $parameters);
        } else {
            $nextPage = null;
        }

        return new JsonResponse([
            'results' => array_map(static function (\Product $product): array {
                $label = $product->name ?? 'Product #' . $product->id;

                if ($product->reference) {
                    $label .= ' (ref. ' . $product->reference . ')';
                }

                return [
                    'value' => $product->id,
                    'text' => $label,
                ];
            }, $results),
            'next_page' => $nextPage,
        ]);
    }

    protected function getRequiredPermissions(): iterable
    {
        yield from parent::getRequiredPermissions();
        yield self::getProductsReadPermission();
    }

    private static function getProductsReadPermission(): array
    {
        return [PageVoter::READ, 'AdminProducts'];
    }

    private function handleUpdateError(\Throwable $e, Request $request): void
    {
        if ($e instanceof HotProductExceptionInterface) {
            $this->addFlash('error', $e->getMessage());
        } elseif ($e instanceof MaxProductLimitReachedException) {
            $this->addFlash('error', $this->translator->l('Maximum number of hot products reached.', self::TRANSLATION_SOURCE));
        } elseif ($e instanceof BasketAppException) {
            $this->addFlash('error', sprintf('Basket App API error: "%s".', $e->getError()->getCode()));
        } elseif ($e instanceof NetworkExceptionInterface || $e instanceof OAuth2ExceptionInterface) {
            $this->addFlash('error', 'API connection error.');
        } elseif ($e instanceof HttpExceptionInterface) {
            $this->addFlash('error', sprintf('Unexpected Basket App API response status code: %d.', $e->getResponse()->getStatusCode()));
        } else {
            $this->handleError($e, $request);
        }
    }
}
