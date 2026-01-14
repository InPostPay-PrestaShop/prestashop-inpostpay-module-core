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
use izi\prestashop\Configuration\ShippingConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\Extension\Exception\ExtensionExceptionInterface;
use izi\prestashop\Extension\Exception\ExtensionServiceException;
use izi\prestashop\Extension\Message\InstallExtensionCommand;
use izi\prestashop\Extension\View\ExtensionViewFactory;
use izi\prestashop\Form\Type\AdvancedConfigurationType;
use izi\prestashop\Form\Type\ConsentsConfigurationType;
use izi\prestashop\Form\Type\GeneralConfigurationType;
use izi\prestashop\Form\Type\GuiConfigurationType;
use izi\prestashop\Form\Type\ShippingConfigurationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="config")
 */
final class ConfigurationController extends AbstractConfigurationController
{
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

                return $this->redirectToRoute('admin_inpost_izi_config_general');
            } catch (\Throwable $e) {
                $this->handleError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/general.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->trans('Settings', [], 'Admin.Global'),
            'headerTabContent' => $this->getNavBar(),
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

                return $this->redirectToRoute('admin_inpost_izi_config_consents');
            } catch (\Throwable $e) {
                $this->handleError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/consents.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->trans('Consents', [], 'Modules.Inpostizi.Config'),
            'headerTabContent' => $this->getNavBar(),
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

                return $this->redirectToRoute('admin_inpost_izi_config_gui');
            } catch (\Throwable $e) {
                $this->handleError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/gui.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->trans('GUI configuration', [], 'Modules.Inpostizi.Config'),
            'headerTabContent' => $this->getNavBar(),
            'widget_js_uri' => $this->apiConfiguration->getEnvironment()->getWidgetJavaScriptUri(),
            'merchant_client_id' => $this->apiConfiguration->getMerchantClientId(),
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

                return $this->redirectToRoute('admin_inpost_izi_config_shipping');
            } catch (\Throwable $e) {
                $this->handleError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/shipping.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->translator->trans('Shipping configuration', [], 'Modules.Inpostizi.Config'),
            'headerTabContent' => $this->getNavBar(),
        ]);
    }

    /**
     * @param AdvancedConfiguration $configuration
     *
     * @Route(path="/support", name="admin_inpost_izi_config_support", methods={"GET"})
     */
    public function support(Request $request, AdvancedConfigurationInterface $configuration, CommandBusInterface $bus, ?ExtensionViewFactory $extensionsViewFactory = null): Response
    {
        $this->checkAccess();

        $form = $this->createForm(AdvancedConfigurationType::class, $configuration->copy(), [
            'action' => $this->generateUrl('admin_inpost_izi_config_support_save', $request->query->all()),
        ]);

        $extensions = null;
        if (null !== $extensionsViewFactory && $this->isGranted('create', 'AdminModulesSf')) {
            try {
                $extensions = $extensionsViewFactory->getView();
            } catch (ExtensionServiceException $e) {
                $this->getLogger()->error('Could not retrieve extensions data. {message}', [
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                $this->handleError($e, $request);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/support.html.twig', [
            'layoutTitle' => $this->translator->trans('Support', [], 'Modules.Inpostizi.Config'),
            'headerTabContent' => $this->getNavBar(),
            'form' => $form->createView(),
            'status' => $bus->handle(new CheckStatusCommand()),
            'extensions' => $extensions,
        ]);
    }

    /**
     * @param AdvancedConfiguration $configuration
     *
     * @Route(path="/support", name="admin_inpost_izi_config_support_save", methods={"POST"})
     */
    public function supportSave(Request $request, AdvancedConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
        if (!$this->isGranted(...self::getConfigPermission())) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Access Denied.',
            ], Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(AdvancedConfigurationType::class, $configuration->copy());
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Malformed request.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$form->isValid()) {
            return new JsonResponse([
                'success' => false,
                'message' => (string) $form->getErrors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
                'message' => $this->trans('Oops... looks like an unexpected error occurred', [], 'Admin.Notifications.Error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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

    /**
     * @Route(path="/install-extension/{name}/{version}", name="admin_inpost_izi_config_install_extension", methods={"POST"})
     */
    public function installExtension(string $name, string $version, Request $request, CommandBusInterface $bus): Response
    {
        $this->checkAccess();

        if (!$this->isCsrfTokenValid('inpost-izi-install-extension', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', $this->translator->trans('The CSRF token is invalid.', [], 'Modules.Inpostizi.Validators'));

            return $this->redirectToRoute('admin_inpost_izi_products_index');
        }

        try {
            $command = new InstallExtensionCommand($name, $version);
            $bus->handle($command);

            $this->addFlash('success', $this->translator->trans('Extensions have been successfully updated.', [], 'Modules.Inpostizi.Extension'));
        } catch (ExtensionServiceException $e) {
            $this->getLogger()->error('Could not retrieve extensions data. {message}', [
                'message' => $e->getMessage(),
            ]);
            $this->addFlash('error', $this->translator->trans('Could not retrieve extensions data: {error}', [
                '{error}' => $e->getMessage(),
            ], 'Modules.Inpostizi.Extension'));
        } catch (ExtensionExceptionInterface $e) {
            $this->getLogger()->error('Could not install extension "{name}" version "{version}". {message}', [
                'name' => $name,
                'version' => $version,
                'message' => $e->getMessage(),
                'exception' => $e->getPrevious(),
            ]);
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->handleError($e, $request);
        }

        return $this->redirectToRoute('admin_inpost_izi_config_support');
    }
}
