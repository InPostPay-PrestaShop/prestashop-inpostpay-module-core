<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Webservice;

use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Webservice\Model\InPostOrder;

/**
 * @phpstan-type WSResource array{
 *     class: class-string<\ObjectModel>,
 *     description: string,
 *     forbidden_method?: string[],
 *     specific_management?: bool,
 * }
 */
final class AddWebserviceResources implements HookInterface
{
    public const HOOK_NAME = 'addWebserviceResources';

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{resources: array<string, WSResource>} $parameters
     *
     * @return array<string, WSResource>
     */
    public function execute(array $parameters): array
    {
        return [
            'inpost_orders' => [
                'class' => InPostOrder::class,
                'description' => 'Customer orders with additional InPost Pay data',
                'forbidden_method' => ['POST', 'PUT', 'PATCH', 'DELETE'],
            ],
        ];
    }
}
