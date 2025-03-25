<?php

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Mail\Resolver\OrderMailRecipientResolver;

class ActionEmailSendBefore implements HookInterface
{
    public const HOOK_NAME = 'actionEmailSendBefore';

    /**
     * @var OrderMailRecipientResolver
     */
    private $mailRecipientResolver;

    public function __construct(OrderMailRecipientResolver $mailRecipientResolver)
    {
        $this->mailRecipientResolver = $mailRecipientResolver;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public function execute(array $parameters): bool
    {
        if (isset($parameters['templateVars']['{id_order}'])) {
            $to = is_array($parameters['to']) ? $parameters['to'] : [$parameters['to']];
            $bcc = empty($parameters['bcc']) ? [] : $parameters['bcc'];
            $bcc = is_array($bcc) ? $bcc : [$bcc];
            $mailRecipient = $this->mailRecipientResolver->resolve($parameters['templateVars']['{id_order}'], $to, $bcc);
            $parameters['to'] = $mailRecipient->getEmails();
            $parameters['bcc'] = $mailRecipient->getBcc();
        }

        return true;
    }
}
