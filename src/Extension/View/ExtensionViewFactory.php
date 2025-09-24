<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\View;

use Composer\Semver\Semver;
use izi\prestashop\Extension\Extension;
use izi\prestashop\Extension\ExtensionsServiceInterface;
use izi\prestashop\Extension\ExtensionVersion;
use izi\prestashop\Module\ModuleRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ExtensionViewFactory
{
    /**
     * @var ExtensionsServiceInterface
     */
    private $service;

    /**
     * @var ModuleRepository
     */
    private $repository;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(ExtensionsServiceInterface $service, ModuleRepository $repository, \Context $context, UrlGeneratorInterface $urlGenerator, string $psVersion = _PS_VERSION_)
    {
        $this->service = $service;
        $this->repository = $repository;
        $this->module = $repository->findByName('inpostizi');
        $this->context = $context;
        $this->urlGenerator = $urlGenerator;
        $this->psVersion = $psVersion;
    }

    public function getView(): ?ExtensionListView
    {
        $extensions = $this->service->getExtensions();

        if ([] === $extensions = $this->filterVersions($extensions)) {
            return null;
        }

        $view = new ExtensionListView();

        foreach ($extensions as $extension) {
            $extView = $this->createExtensionView($extension);

            if ($version = $this->getInstalledVersion($extension->getName())) {
                $extView->setUpgradeable(\Tools::version_compare($version, $extView->getVersion()));
                $view->addInstalled($extView);
            } elseif (null !== $this->getInstalledVersion($extension->getModule())) {
                $view->addRecommended($extView);
            } else {
                $view->addOther($extView);
            }
        }

        return $view;
    }

    /**
     * @param Extension[] $extensions
     *
     * @return Extension[]
     */
    private function filterVersions(array $extensions): array
    {
        $filtered = [];

        foreach ($extensions as $extension) {
            if (null === $extension = $this->limitToLatestCompatibleVersion($extension)) {
                continue;
            }

            $filtered[] = $extension;
        }

        return $filtered;
    }

    private function createExtensionView(Extension $extension): ExtensionView
    {
        $version = $extension->getVersions()[0]->getVersion();

        $url = $this->urlGenerator->generate('admin_inpost_izi_config_install_extension', [
            'name' => $extension->getName(),
            'version' => $version,
        ]);

        $language = (string) $this->context->language->iso_code;
        $name = $extension->getDisplayName($language) ?? $extension->getDisplayName('en') ?? $extension->getName();
        $description = $extension->getDescription($language) ?? $extension->getDescription('en');

        return new ExtensionView(
            $name,
            $version,
            $url,
            $description,
            $extension->getModule()
        );
    }

    private function limitToLatestCompatibleVersion(Extension $extension): ?Extension
    {
        $module = $extension->getModule();

        foreach ($extension->getVersions() as $version) {
            if ($this->isCompatible($version, $module)) {
                return $extension->withVersions([$version]);
            }
        }

        return null;
    }

    private function isCompatible(ExtensionVersion $extension, ?string $moduleName): bool
    {
        if (!$this->checkCompatibility($extension, 'prestashop', $this->psVersion)) {
            return false;
        }

        if (!$this->checkCompatibility($extension, $this->module->name, $this->module->version)) {
            return false;
        }

        if (null === $moduleName) {
            return true;
        }

        // keep the extension if the associated module is not installed
        if (null === $module = $this->repository->findByName($moduleName)) {
            return true;
        }

        return $this->checkCompatibility($extension, $moduleName, $module->version);
    }

    private function checkCompatibility(ExtensionVersion $extension, string $dependency, string $version): bool
    {
        if (null === $constraint = $extension->getVersionsConstraint($dependency)) {
            return true;
        }

        return Semver::satisfies($version, $constraint);
    }

    private function getInstalledVersion(string $name): ?string
    {
        if (null === $module = $this->repository->findByName($name)) {
            return null;
        }

        if (0 >= (int) $module->id) {
            return null;
        }

        return $module->version;
    }
}
