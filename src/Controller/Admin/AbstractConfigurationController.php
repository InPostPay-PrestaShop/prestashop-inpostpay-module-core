<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\Initializer\ConfigurationInitializerInterface;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Security\Voter\PageVoter;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Role\Role;

/* IGNORE_THIS_FILE_FOR_TRANSLATION */
abstract class AbstractConfigurationController extends AbstractController
{
    use LoggerAwareTrait;

    /**
     * @var LegacyTranslator
     */
    protected $translator;

    /**
     * @var \Context
     */
    protected $context;

    /**
     * @var ApiConfigurationInterface
     */
    protected $apiConfiguration;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param iterable<ConfigurationInitializerInterface> $configInitializers
     */
    public function __construct(LegacyTranslator $translator, \Context $context, iterable $configInitializers, ApiConfigurationInterface $apiConfiguration, bool $debug = false)
    {
        $this->translator = $translator;
        $this->context = $context;
        $this->apiConfiguration = $apiConfiguration;
        $this->debug = $debug;

        foreach ($configInitializers as $initializer) {
            $initializer->init();
        }
    }

    /**
     * @param string $view
     *
     * @internal public visibility for compatibility with Sf 2.8
     */
    public function render($view, array $parameters = [], Response $response = null): Response
    {
        $parameters['is_legacy_admin_page'] = version_compare(_PS_VERSION_, '1.7.4.0', '<');

        return parent::render($view, $parameters, $response);
    }

    final protected static function getConfigPermission(): array
    {
        $role = \Access::sluggifyModule([
            'name' => 'inpostizi',
        ], \Access::getAuthorizationFromLegacy('configure'));

        return [$role, null];
    }

    protected function checkAccess(): void
    {
        foreach ($this->getRequiredPermissions() as [$attributes, $subject]) {
            $this->denyAccessUnlessGranted($attributes, $subject);
        }
    }

    /**
     * @return iterable<array{0: string|string[], 1: mixed}>
     */
    protected function getRequiredPermissions(): iterable
    {
        yield self::getConfigPermission();
    }

    protected function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->context->getTranslator()->trans($id, $parameters, $domain, $locale);
    }

    protected function renderNav(Request $request): string
    {
        $pages = [
            'general' => [
                'route' => 'admin_inpost_izi_config_general',
                'title' => $this->translator->l('Configuration', ConfigurationController::TRANSLATION_SOURCE),
            ],
            'consents' => [
                'route' => 'admin_inpost_izi_config_consents',
                'title' => $this->translator->l('Consents', ConfigurationController::TRANSLATION_SOURCE),
            ],
            'shipping' => [
                'route' => 'admin_inpost_izi_config_shipping',
                'title' => $this->translator->l('Shipping configuration', ConfigurationController::TRANSLATION_SOURCE),
            ],
            'gui' => [
                'route' => 'admin_inpost_izi_config_gui',
                'title' => $this->translator->l('GUI configuration', ConfigurationController::TRANSLATION_SOURCE),
            ],
            'support' => [
                'route' => 'admin_inpost_izi_config_support',
                'title' => $this->translator->l('Support', ConfigurationController::TRANSLATION_SOURCE),
            ],
        ];

        if (null !== $this->apiConfiguration->getClientCredentials() && $this->isGranted(PageVoter::READ, 'AdminProducts_')) {
            $pages['products'] = [
                'route' => 'admin_inpost_izi_products_index',
                'title' => $this->translator->l('Hot products', HotProductController::TRANSLATION_SOURCE),
                'active_checker' => static function (Request $request): bool {
                    return $request->attributes->has('_inpost_izi_hot_product_page');
                },
            ];
        }

        return $this->renderView('@Modules/inpostizi/views/templates/admin/config/nav.html.twig', [
            'nav_items' => array_map(function (array $page) use ($request): array {
                return [
                    'url' => $this->generateUrl($page['route']),
                    'label' => $page['title'],
                    'active' => isset($page['active_checker'])
                        ? $page['active_checker']($request)
                        : $page['route'] === $request->attributes->get('_route'),
                ];
            }, $pages),
        ]);
    }

    protected function handleError(\Throwable $e, Request $request): void
    {
        $this->logger = $this->logger ?? new NullLogger();
        $this->logger->critical('An error occurred while processing the request: {exception}', [
            'route' => $request->attributes->get('_route'),
            'exception' => $e,
        ]);

        if ($this->debug) {
            throw $e;
        }

        $this->addFlash('error', $this->trans('An unexpected error occurred. [%type% code %code%]', [
            '%type%' => get_class($e),
            '%code%' => $e->getCode(),
        ], 'Admin.Notifications.Error'));
    }

    final protected function isGranted($attributes, $subject = null): bool
    {
        if (parent::isGranted($attributes, $subject)) {
            return true;
        }

        if (null !== $subject || !str_starts_with($attributes, 'ROLE_')) {
            return false;
        }

        return $this->checkUserRole($attributes);
    }

    /**
     * Checks module configuration role using the user entity instead of the security token, since the token may not contain
     * all the user roles if it was reloaded from session.
     *
     * Implementing a custom security voter (PS does not provide a default one) would have been a cleaner solution,
     * but that could cause problems if the container cache was not refreshed after disabling or uninstalling the module.
     */
    private function checkUserRole(string $role): bool
    {
        if (null === $user = $this->getUser()) {
            return false;
        }

        $userRoles = array_map(static function ($role): string {
            if ($role instanceof Role) {
                return $role->getRole();
            }

            return (string) $role;
        }, $user->getRoles());

        return in_array($role, $userRoles, true);
    }
}
