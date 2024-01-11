<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

final class TypedReference extends Reference
{
    private $type;

    public function __construct(string $id, string $type, int $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    {
        $this->type = $type;

        parent::__construct($id, $invalidBehavior);
    }

    public function getType(): string
    {
        return $this->type;
    }
}
