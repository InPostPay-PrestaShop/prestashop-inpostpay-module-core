<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config\Status;

use Symfony\Component\DependencyInjection\ContainerInterface;

final class CacheStatusChecker implements StatusCheckerInterface
{
    private const TRANSLATION_SOURCE = 'cachestatuschecker';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(\Module $module, ContainerInterface $container)
    {
        $this->module = $module;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function checkStatus(): array
    {
        if ($this->checkContainerVersion()) {
            return [];
        }

        return [$this->module->l('Container cache is stale.', self::TRANSLATION_SOURCE)];
    }

    private function checkContainerVersion(): bool
    {
        if (!$this->container->hasParameter('inpost.izi.container_version')) {
            return false;
        }

        return $this->module->version === $this->container->getParameter('inpost.izi.container_version');
    }
}
