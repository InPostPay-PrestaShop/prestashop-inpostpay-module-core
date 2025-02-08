<?php

declare(strict_types=1);

namespace izi\prestashop\Form\TypeExtension;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\WidgetVersionCheckerTrait;
use izi\prestashop\Form\Type\Widget\WidgetConfigurationType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
final class VersionCheckingWidgetConfigurationTypeExtension extends AbstractTypeExtension
{
    use WidgetVersionCheckerTrait;

    public function __construct(ApiConfigurationInterface $apiConfiguration)
    {
        $this->apiConfiguration = $apiConfiguration;
    }

    public static function getExtendedTypes(): iterable
    {
        return [WidgetConfigurationType::class];
    }

    public function getExtendedType(): string
    {
        return WidgetConfigurationType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'for_v2' => $this->isWidgetV2Enabled(),
        ]);
    }
}
