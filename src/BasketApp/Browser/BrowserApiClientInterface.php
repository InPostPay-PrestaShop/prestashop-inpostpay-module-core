<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Browser;

use izi\prestashop\BasketApp\Exception\BrowserNotFoundException;

/**
 * @deprecated
 */
interface BrowserApiClientInterface
{
    /**
     * @throws BrowserNotFoundException
     */
    public function deleteBrowserBinding(string $browserId): void;
}
