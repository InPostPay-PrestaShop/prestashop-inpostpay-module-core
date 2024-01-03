<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection;

use Symfony\Component\DependencyInjection\ServiceSubscriberInterface as LegacyServiceSubscriber;
use Symfony\Contracts\Service\ServiceSubscriberInterface as ContractsServiceSubscriber;

if (!interface_exists(LegacyServiceSubscriber::class)) {
    interface ServiceSubscriberInterface extends ContractsServiceSubscriber
    {
    }
} else {
    interface ServiceSubscriberInterface extends LegacyServiceSubscriber
    {
    }
}
