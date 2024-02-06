<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommandFactory;
use izi\prestashop\Command\Config\UpdateGuiConfigurationCommand;
use izi\prestashop\Command\Config\UpdateShippingConfigurationCommandFactory;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\ShippingAmpConfiguration;
use izi\prestashop\Form\Type\ConsentsConfigurationType;
use izi\prestashop\Form\Type\GeneralConfigurationType;
use izi\prestashop\Form\Type\GuiConfigurationType;
use izi\prestashop\Form\Type\ShippingConfigurationType;
use PrestaShopBundle\Security\Voter\PageVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="config", name="admin_inpost_izi_config_", defaults={"_legacy_controller"=ConfigurationController::TAB_NAME, "_legacy_link"=ConfigurationController::TAB_NAME})
 */
final class ConfigurationController extends AbstractController
{
    public const TAB_NAME = 'AdminInPostIziConfiguration';
    private const TRANSLATION_SOURCE = 'configurationcontroller';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    /**
     * @Route(path="/general", name="general", methods={"GET", "POST"})
     */
    public function generalConfig(Request $request, UpdateGeneralConfigurationCommandFactory $commandFactory, CommandBusInterface $bus): Response
    {
//        $this->denyAccessUnlessGranted(PageVoter::READ, self::TAB_NAME);

        $command = $commandFactory->create();

        $form = $this->createForm(GeneralConfigurationType::class, $command, [
            'disabled' => !$canUpdate = true || $this->isGranted(PageVoter::UPDATE, self::TAB_NAME),
        ]);

        if ($form->handleRequest($request) && $form->isSubmitted() && $form->isValid()) {
            try {
                $bus->handle($command);
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_general');
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/general.html.twig', [
            'form' => $form->createView(),
            'can_update' => $canUpdate,
            'layoutTitle' => $this->module->l('API configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
        ]);
    }

    /**
     * @Route(path="/consents", name="consents", methods={"GET", "POST"})
     */
    public function consentConfig(Request $request, ConsentsConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
//        $this->denyAccessUnlessGranted(PageVoter::READ, self::TAB_NAME);

        $consents = $configuration->getConsents() ?: [new Consent()];
        $command = new UpdateConsentsConfigurationCommand(...$consents);

        $form = $this->createForm(ConsentsConfigurationType::class, $command, [
            'disabled' => !$canUpdate = true || $this->isGranted(PageVoter::UPDATE, self::TAB_NAME),
        ]);

        if ($form->handleRequest($request) && $form->isSubmitted() && $form->isValid()) {
            try {
                $bus->handle($command);
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_consents');
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/consents.html.twig', [
            'form' => $form->createView(),
            'can_update' => $canUpdate,
            'layoutTitle' => $this->module->l('Consents', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
        ]);
    }

    /**
     * @param GuiConfiguration $configuration
     *
     * @Route(path="/gui", name="gui", methods={"GET", "POST"})
     */
    public function guiConfig(Request $request, GuiConfigurationInterface $configuration, CommandBusInterface $bus): Response
    {
//        $this->denyAccessUnlessGranted(PageVoter::READ, self::TAB_NAME);

        $form = $this->createForm(GuiConfigurationType::class, $configuration->copy(), [
            'disabled' => !$canUpdate = true || $this->isGranted(PageVoter::UPDATE, self::TAB_NAME),
        ]);

        if ($form->handleRequest($request) && $form->isSubmitted() && $form->isValid()) {
            $command = new UpdateGuiConfigurationCommand($form->getData());

            try {
                $bus->handle($command);
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_gui');
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/gui.html.twig', [
            'form' => $form->createView(),
            'can_update' => $canUpdate,
            'layoutTitle' => $this->module->l('GUI configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
        ]);
    }

    /**
     * @param UpdateShippingConfigurationCommandFactory $commandFactory
     *
     * @Route(path="/shipping", name="shipping", methods={"GET", "POST"})
     */
    public function shippingConfig(Request $request, UpdateShippingConfigurationCommandFactory $commandFactory, CommandBusInterface $bus): Response
    {
//        $this->denyAccessUnlessGranted(PageVoter::READ, self::TAB_NAME);
        $command = $commandFactory->create();

        $form = $this->createForm(ShippingConfigurationType::class, $command, [
            'disabled' => !$canUpdate = true || $this->isGranted(PageVoter::UPDATE, self::TAB_NAME),
        ]);

        if ($form->handleRequest($request) && $form->isSubmitted() && $form->isValid()) {
            try {
                $bus->handle($command);
                $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_inpost_izi_config_shipping');
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/shipping.html.twig', [
            'form' => $form->createView(),
            'can_update' => $canUpdate,
            'layoutTitle' => $this->module->l('Shipping configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav($request),
        ]);
    }

    private function renderNav(Request $request): string
    {
        $pages = [
            'general' => [
                'route' => 'admin_inpost_izi_config_general',
                'title' => $this->module->l('API configuration', self::TRANSLATION_SOURCE),
            ],
            'consents' => [
                'route' => 'admin_inpost_izi_config_consents',
                'title' => $this->module->l('Consents', self::TRANSLATION_SOURCE),
            ],
            'shipping' => [
                'route' => 'admin_inpost_izi_config_shipping',
                'title' => $this->module->l('Shipping configuration', self::TRANSLATION_SOURCE),
            ],
            'gui' => [
                'route' => 'admin_inpost_izi_config_gui',
                'title' => $this->module->l('GUI configuration', self::TRANSLATION_SOURCE),
            ],
        ];

        return $this->renderView('@Modules/inpostizi/views/templates/admin/config/nav.html.twig', [
            'nav_items' => array_map(function (array $page) use ($request): array {
                return [
                    'url' => $this->generateUrl($page['route']),
                    'label' => $page['title'],
                    'active' => $page['route'] === $request->attributes->get('_route'),
                ];
            }, $pages),
        ]);
    }

    private function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
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
}
