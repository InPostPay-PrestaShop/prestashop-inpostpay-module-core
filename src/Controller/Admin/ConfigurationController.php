<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommandFactory;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfiguration;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Form\Type\GeneralConfigurationType;
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
     *
     * @param ApiConfiguration $apiConfiguration,
     * @param OrdersConfiguration $ordersConfiguration
     */
    public function generalConfig(Request $request, UpdateGeneralConfigurationCommandFactory $commandFactory, CommandBusInterface $bus): Response
    {
//        $this->denyAccessUnlessGranted(PageVoter::READ, self::TAB_NAME);

        $command = $commandFactory->create();

        $form = $this->createForm(GeneralConfigurationType::class, $command, [
            'disabled' => !$canUpdate = true || $this->isGranted(PageVoter::UPDATE, self::TAB_NAME),
        ]);

        if ($form->handleRequest($request) && $form->isSubmitted() && $form->isValid()) {
            $bus->handle($command);
            $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));

            return $this->redirectToRoute('admin_inpost_izi_config_general');
        }

        return $this->render('@Modules/inpostizi/views/templates/admin/config/general.html.twig', [
            'form' => $form->createView(),
            'can_update' => $canUpdate,
            'layoutTitle' => $this->module->l('API configuration', self::TRANSLATION_SOURCE),
            'headerTabContent' => $this->renderNav('general'),
        ]);
    }

    private function renderNav(string $activeTab): string
    {
        return $this->renderView('@Modules/inpostizi/views/templates/admin/config/nav.html.twig', [
            'nav_items' => [
                'general' => [
                    'url' => $this->generateUrl('admin_inpost_izi_config_general'),
                    'label' => $this->module->l('API configuration', self::TRANSLATION_SOURCE),
                    'active' => 'general' === $activeTab,
                ],
            ],
        ]);
    }

    private function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->context->getTranslator()->trans($id, $parameters, $domain, $locale);
    }
}
