<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

/**
 * @method array<string, string|null> getParameters()
 * @method string|null getParameter(string $name)
 */
interface BasketAnalyticsInterface
{
    /**
     * @deprecated use {@see getParameter()} passing "gclid" as an argument instead
     */
    public function getGclid(): ?string;

    /**
     * @deprecated use {@see getParameter()} passing "fbclid" as an argument instead
     */
    public function getFbclid(): ?string;

    /**
     * @deprecated use {@see getParameter()} with "client_id" as an argument instead
     */
    public function getClientId(): ?string;

    public function isEmpty(): bool;
}
