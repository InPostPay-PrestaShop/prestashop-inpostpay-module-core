<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Analytics\Cookie\CookiePersisterInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DisplayHeader implements HookInterface
{
    public const HOOK_NAME = 'displayHeader';

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $basketSessionRepository;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var CookiePersisterInterface
     */
    private $analyticsCookiePersister;

    public function __construct(
        \Context $context,
        GeneralConfigurationInterface $generalConfiguration,
        ApiConfigurationInterface $apiConfiguration,
        AuthorizationCheckerInterface $authorizationChecker,
        CookiePersisterInterface $analyticsCookiePersister,
        BasketSessionRepositoryInterface $basketSessionRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->basketSessionRepository = $basketSessionRepository;
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
        $this->generalConfiguration = $generalConfiguration;
        $this->apiConfiguration = $apiConfiguration;
        $this->authorizationChecker = $authorizationChecker;
        $this->analyticsCookiePersister = $analyticsCookiePersister;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    private function shouldRunUpdateContext(): bool
    {
        if (!isset($this->context->cart->id) || !$this->context->cookie->exists() || !$this->context->shop->getGroup()->share_order) {
            return false;
        }

        return true;
    }

    /**
     * @param array{request?: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        if ($this->shouldRunUpdateContext()) {
            $session = $this->basketSessionRepository->findByEntityId($this->context->cart->id);

            if (null === $session || $session->getShopId() === (int) $this->context->shop->id) {
                return;
            }

            $session->setShopId($this->context->shop->id);
            $this->basketSessionRepository->persist($session);

            $this->eventDispatcher->dispatch(new CartUpdatedEvent($this->context->cart));
        }

        $request = $parameters['request'] ?? null;

        if (($request instanceof Request && $request->isXmlHttpRequest()) || !$this->hasRequiredConfiguration() || !$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)) {
            return;
        }

        $this->analyticsCookiePersister->persist($request);
    }

    private function hasRequiredConfiguration(): bool
    {
        return $this->generalConfiguration->isSendAnalyticsData() && null !== $this->apiConfiguration->getClientCredentials() && null !== $this->apiConfiguration->getMerchantClientId();
    }
}
