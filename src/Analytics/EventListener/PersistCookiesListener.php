<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\EventListener;

use izi\prestashop\Analytics\Cookie\CookiePersisterInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\Front\Event\RenderHeaderEvent;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class PersistCookiesListener implements EventSubscriberInterface
{
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
    private $cookiePersister;

    public function __construct(GeneralConfigurationInterface $generalConfiguration, ApiConfigurationInterface $apiConfiguration, AuthorizationCheckerInterface $authorizationChecker, CookiePersisterInterface $cookiePersister)
    {
        $this->generalConfiguration = $generalConfiguration;
        $this->apiConfiguration = $apiConfiguration;
        $this->authorizationChecker = $authorizationChecker;
        $this->cookiePersister = $cookiePersister;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RenderHeaderEvent::class => 'onFrontOfficeRequest',
        ];
    }

    public function onFrontOfficeRequest(RenderHeaderEvent $event): void
    {
        $request = $event->getRequest();

        if (null === $request || $request->isXmlHttpRequest()) {
            return;
        }

        if (!$this->hasRequiredConfiguration()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)) {
            return;
        }

        $this->cookiePersister->persist($request);
    }

    private function hasRequiredConfiguration(): bool
    {
        return $this->generalConfiguration->isSendAnalyticsData()
            && null !== $this->apiConfiguration->getClientCredentials()
            && null !== $this->apiConfiguration->getMerchantClientId();
    }
}
