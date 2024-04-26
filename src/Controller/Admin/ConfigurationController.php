<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use izi\prestashop\Command\Config\CheckStatusCommand;
use izi\prestashop\Command\Config\DownloadModuleDataCommand;
use izi\prestashop\Command\Config\UpdateAdvancedConfigurationCommand;
use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommandFactory;
use izi\prestashop\Command\Config\UpdateGuiConfigurationCommand;
use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\AdvancedConfiguration;
use izi\prestashop\Configuration\AdvancedConfigurationInterface;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\Initializer\ConfigurationInitializerInterface;
use izi\prestashop\Configuration\ShippingConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\Form\Type\AdvancedConfigurationType;
use izi\prestashop\Form\Type\ConsentsConfigurationType;
use izi\prestashop\Form\Type\GeneralConfigurationType;
use izi\prestashop\Form\Type\GuiConfigurationType;
use izi\prestashop\Form\Type\ShippingConfigurationType;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="config")
 */
final class ConfigurationController extends AbstractController
{
    private const TRANSLATION_SOURCE = 'configurationcontroller';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @param iterable<ConfigurationInitializerInterface> $configInitializers
     */
    public function __construct(LegacyTranslator $translator, \Context $context, iterable $configInitializers = [])
    {
        $this->translator = $translator;
        $this->context = $context;

        foreach ($configInitializers as $initializer) {
            $initializer->init();
        }
    }

    /**
     * @Route(path="/general", name="admin_inpost_izi_config_general", methods={"GET", "POST"})
     */
    public function generalConfig(Request $request, UpdateGeneralConfigurationCommandFactory $commandFactory, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        $command = $commandFactory->create();

        $form = $this->createForm(GeneralConfigurationType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $bus->handle($form->getData());
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_general', $request->query->all());
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/general.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->l('Configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
            'is_legacy_admin_page' => $this->isLegacyAdminPage(),
        ]);
    }

    /**
     * @Route(path="/consents", name="admin_inpost_izi_config_consents", methods={"GET", "POST"})
     */
    public function consentConfig(Request $request, ConsentsConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        $command = new UpdateConsentsConfigurationCommand(...$configuration->getConsents());

        $form = $this->createForm(ConsentsConfigurationType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $bus->handle($form->getData());
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_consents', $request->query->all());
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/consents.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->l('Consents', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
            'is_legacy_admin_page' => $this->isLegacyAdminPage(),
        ]);
    }

    /**
     * @param GuiConfiguration $configuration
     *
     * @Route(path="/gui", name="admin_inpost_izi_config_gui", methods={"GET", "POST"})
     */
    public function guiConfig(Request $request, GuiConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        $form = $this->createForm(GuiConfigurationType::class, $configuration->copy());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new UpdateGuiConfigurationCommand($form->getData());

            try {
                $bus->handle($command);
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_gui', $request->query->all());
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/gui.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->l('GUI configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
            'is_legacy_admin_page' => $this->isLegacyAdminPage(),
        ]);
    }

    /**
     * @param ShippingConfiguration $configuration
     *
     * @Route(path="/shipping", name="admin_inpost_izi_config_shipping", methods={"GET", "POST"})
     */
    public function shippingConfig(Request $request, ShippingConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        $form = $this->createForm(ShippingConfigurationType::class, $configuration->copy());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new UpdateShippingConfigurationCommand($form->getData());

            try {
                $bus->handle($command);
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_shipping', $request->query->all());
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/shipping.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->l('Shipping configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
            'is_legacy_admin_page' => $this->isLegacyAdminPage(),
        ]);
    }

    /**
     * @param AdvancedConfiguration $configuration
     *
     * @Route(path="/support", name="admin_inpost_izi_config_support", methods={"GET"})
     */
    public function support(Request $request, AdvancedConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        $form = $this->createForm(AdvancedConfigurationType::class, $configuration->copy(), [
            'action' => $this->generateUrl('admin_inpost_izi_config_support_save', $request->query->all()),
        ]);

        return $this->render('@Modules/inpostizi/views/templates/admin/config/support.html.twig', [
            'layoutTitle' => $this->translator->l('Support', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
            'form' => $form->createView(),
            'status' => $bus->handle(new CheckStatusCommand()),
            'is_legacy_admin_page' => $this->isLegacyAdminPage(),
        ]);
    }

    /**
     * @param AdvancedConfiguration $configuration
     *
     * @Route(path="/support", name="admin_inpost_izi_config_support_save", methods={"POST"})
     */
    public function supportSave(Request $request, AdvancedConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
        if (!$this->checkAccess(false)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Access Denied.',
            ], 403);
        }

        $form = $this->createForm(AdvancedConfigurationType::class, $configuration->copy());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $command = new UpdateAdvancedConfigurationCommand($form->getData());

            try {
                $bus->handle($command);

                return new JsonResponse([
                    'success' => true,
                    'message' => $this->trans('Successful update.', [], 'Admin.Notifications.Success'),
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        } else {
            return new JsonResponse([
                'success' => false,
                'message' => $this->trans('Oops... looks like an unexpected error occurred', [], 'Admin.Notifications.Error'),
            ], 400);
        }
    }

    /**
     * @Route(path="/download-data", name="admin_inpost_izi_config_download_data", methods={"GET"})
     */
    public function downloadData(CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        $callback = $bus->handle(new DownloadModuleDataCommand());
        $response = new StreamedResponse($callback);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'module_data.zip');

        $response->headers->add([
            'Content-Type' => 'application/x-zip',
            'Content-Disposition' => $disposition,
        ]);

        return $response;
    }

    private function renderNav(Request $request): string
    {
        $pages = [
            'general' => [
                'route' => 'admin_inpost_izi_config_general',
                'title' => $this->translator->l('Configuration', self::TRANSLATION_SOURCE),
            ],
            'consents' => [
                'route' => 'admin_inpost_izi_config_consents',
                'title' => $this->translator->l('Consents', self::TRANSLATION_SOURCE),
            ],
            'shipping' => [
                'route' => 'admin_inpost_izi_config_shipping',
                'title' => $this->translator->l('Shipping configuration', self::TRANSLATION_SOURCE),
            ],
            'gui' => [
                'route' => 'admin_inpost_izi_config_gui',
                'title' => $this->translator->l('GUI configuration', self::TRANSLATION_SOURCE),
            ],
            'support' => [
                'route' => 'admin_inpost_izi_config_support',
                'title' => $this->translator->l('Support', self::TRANSLATION_SOURCE),
            ],
        ];

        return $this->renderView('@Modules/inpostizi/views/templates/admin/config/nav.html.twig', [
            'nav_items' => array_map(function (array $page) use ($request): array {
                return [
                    'url' => $this->generateUrl($page['route'], $request->query->all()),
                    'label' => $page['title'],
                    'active' => $page['route'] === $request->attributes->get('_route'),
                ];
            }, $pages),
        ]);
    }

    private function isLegacyAdminPage(): bool
    {
        return version_compare(_PS_VERSION_, '1.7.4.0', '<');
    }

    private function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->context->getTranslator()->trans($id, $parameters, $domain, $locale);
    }

    private function handleException(\Exception $e): void
    {
        if ($this->getParameter('kernel.debug')) {
            throw $e;
        }

        $this->addFlash('error', $this->trans('An unexpected error occurred. [%type% code %code%]', [
            '%type%' => get_class($e),
            '%code%' => $e->getCode(),
        ], 'Admin.Notifications.Error'));
    }

    private function checkAccess(bool $throw = true): bool
    {
        $moduleId = \Module::getModuleIdByName('inpostizi');
        $hasAccess = \Module::getPermissionStatic($moduleId, 'configure');

        if ($throw && !$hasAccess) {
            throw $this->createAccessDeniedException();
        }

        return $hasAccess;
    }
}
