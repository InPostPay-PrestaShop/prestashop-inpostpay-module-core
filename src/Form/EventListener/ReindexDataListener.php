<?php

declare(strict_types=1);

namespace izi\prestashop\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class ReindexDataListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => ['onPreSubmit', 50],
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData() ?? [];

        if (!is_array($data)) {
            return;
        }

        $event->setData(array_values($data));
    }
}
