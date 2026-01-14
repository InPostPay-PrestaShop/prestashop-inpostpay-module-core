<?php

// keep until we find a better way to extract constraint validator messages

use Symfony\Contracts\Translation\TranslatorInterface;

assert(isset($translator) && $translator instanceof TranslatorInterface);

/* @see \izi\prestashop\Validator\ProcessableMessageFormatValidator */
$translator->trans('Invalid message format. {{ error }}', [], 'Modules.Inpostizi.Validators');

/* @see \izi\prestashop\Validator\Consent\DescriptionUsesIdPlaceholdersValidator */
$translator->trans('Unused ID placeholders: "{{ placeholders}}".', [], 'Modules.Inpostizi.Validators');
$translator->trans('Duplicated ID placeholders: "{{ placeholders }}".', [], 'Modules.Inpostizi.Validators');

/* @see \InPost\International\Configuration\Validator\ApiCredentialsValidator */
$translator->trans('Invalid client credentials.', [], 'Modules.Inpostizi.Validators');
$translator->trans('Could not connect to the authorization server.', [], 'Modules.Inpostizi.Validators');
$translator->trans('Could not validate client credentials.', [], 'Modules.Inpostizi.Validators');
$translator->trans('The granted access token does not have all of the required permissions. To resolve this issue, please contact support.', [], 'Modules.Inpostizi.Validators');

/* @see \izi\prestashop\Validator\Consent\UniqueIdentifiersValidator */
$translator->trans('Identifier is not unique.', [], 'Modules.Inpostizi.Validators');
