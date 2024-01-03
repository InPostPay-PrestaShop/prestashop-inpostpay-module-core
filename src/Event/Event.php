<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

use Symfony\Component\EventDispatcher\Event as LegacyEvent;
use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

if (class_exists(Event::class)) {
    class Event extends BaseEvent
    {
    }
} else {
    class Event extends LegacyEvent
    {
    }
}
