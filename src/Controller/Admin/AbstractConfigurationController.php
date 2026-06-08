<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Admin;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\Initializer\ConfigurationInitializerInterface;
use izi\prestashop\View\Component\NavBar;
use izi\prestashop\View\Component\NavItem;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractConfigurationController extends AbstractController
{
    use LoggerAwareTrait;

    /**
     * @var TranslatorInterface
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
     * @internal
     *
     * @var bool
     */
    protected $hotProductsEnabled;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param iterable<ConfigurationInitializerInterface> $configInitializers
     */
    public function __construct(TranslatorInterface $translator, \Context $context, iterable $configInitializers, ApiConfigurationInterface $apiConfiguration, bool $debug = false, bool $hotProductsEnabled = false)
    {
        $this->translator = $translator;
        $this->context = $context;
        $this->apiConfiguration = $apiConfiguration;
        $this->debug = $debug;
        $this->hotProductsEnabled = $hotProductsEnabled;

        foreach ($configInitializers as $initializer) {
            $initializer->init();
        }
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

    final protected function getNavBar(): NavBar
    {
        return new NavBar($this->container->get('twig'), $this->getNavItems());
    }

    protected function getNavItems(): iterable
    {
        $pages = [
            'general' => [
                'route' => 'admin_inpost_izi_config_general',
                'title' => $this->translator->trans('Settings', [], 'Admin.Global'),
            ],
            'consents' => [
                'route' => 'admin_inpost_izi_config_consents',
                'title' => $this->translator->trans('Consents', [], 'Modules.Inpostizi.Config'),
            ],
            'shipping' => [
                'route' => 'admin_inpost_izi_config_shipping',
                'title' => $this->translator->trans('Shipping configuration', [], 'Modules.Inpostizi.Config'),
            ],
            'gui' => [
                'route' => 'admin_inpost_izi_config_gui',
                'title' => $this->translator->trans('GUI configuration', [], 'Modules.Inpostizi.Config'),
            ],
            'support' => [
                'route' => 'admin_inpost_izi_config_support',
                'title' => $this->translator->trans('Support', [], 'Modules.Inpostizi.Config'),
            ],
        ];

        if (
            $this->hotProductsEnabled
            && null !== $this->apiConfiguration->getClientCredentials()
            && $this->isGranted('read', 'AdminProducts')
        ) {
            $pages['products'] = [
                'route' => 'admin_inpost_izi_products_index',
                'title' => $this->translator->trans('Hot products', [], 'Modules.Inpostizi.Config'),
                'active_checker' => static function (Request $request): bool {
                    return $request->attributes->has('_inpost_izi_hot_product_page');
                },
            ];
        }

        foreach ($pages as $id => $page) {
            yield new NavItem($id, $page['title'], $page['route'], $page['active_checker'] ?? null);
        }
    }

    protected function handleError(\Throwable $e, Request $request): void
    {
        $this->getLogger()->critical('An error occurred while processing the configuration page request.', [
            'exception' => $e,
            'route' => $request->attributes->get('_route'),
        ]);

        if ($this->debug) {
            throw $e;
        }

        $this->addFlash('error', $this->trans('An unexpected error occurred. [%type% code %code%]', [
            '%type%' => \get_class($e),
            '%code%' => $e->getCode(),
        ], 'Admin.Notifications.Error'));
    }

    final protected function getLogger(): LoggerInterface
    {
        return $this->logger ?? $this->logger = new NullLogger();
    }
}
