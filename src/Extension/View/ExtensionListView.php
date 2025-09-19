<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\View;

final class ExtensionListView
{
    /**
     * @var ExtensionView[]
     */
    private $installed = [];

    /**
     * @var ExtensionView[]
     */
    private $recommended = [];

    /**
     * @var ExtensionView[]
     */
    private $other = [];

    /**
     * @return ExtensionView[]
     */
    public function getInstalled(): array
    {
        return $this->installed;
    }

    public function addInstalled(ExtensionView $extension): void
    {
        $this->installed[] = $extension;
    }

    /**
     * @return ExtensionView[]
     */
    public function getRecommended(): array
    {
        return $this->recommended;
    }

    public function addRecommended(ExtensionView $extension): void
    {
        $this->recommended[] = $extension;
    }

    /**
     * @return ExtensionView[]
     */
    public function getOther(): array
    {
        return $this->other;
    }

    public function addOther(ExtensionView $extension): void
    {
        $this->other[] = $extension;
    }
}
