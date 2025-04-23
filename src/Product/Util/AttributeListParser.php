<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Util;

use izi\prestashop\Configuration\PrestaShopConfiguration;

/**
 * @internal
 */
final class AttributeListParser
{
    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(PrestaShopConfiguration $configuration, \Context $context, string $psVersion)
    {
        $this->configuration = $configuration;
        $this->context = $context;
        $this->psVersion = $psVersion;
    }

    /**
     * @return array{group: string, name: string}[]
     */
    public function parse(string $attributes, ?int $shopId = null): array
    {
        $pattern = $this->getPattern(
            $colon = $this->getColon(),
            $separator = $this->getSeparator($shopId)
        );

        if (!preg_match_all($pattern, $attributes . $separator, $matches)) {
            return [];
        }

        $result = [];

        foreach ($matches['attribute'] as $attribute) {
            [$group, $name] = array_map('trim', explode($colon, $attribute, 2));

            if ('' === $group || '' === $name) {
                continue;
            }

            $result[] = [
                'group' => $group,
                'name' => $name,
            ];
        }

        return $result;
    }

    private function getPattern(string $colon, string $separator): string
    {
        if ('' === $colonChar = trim($colon)) {
            return strtr('/(?>(?P<attribute>.+?{colon}.+?){separator}(?!{separator}(?:[^{separator_char}])+{colon}))/', [
                '{colon}' => $colon,
                '{separator}' => $separator,
                '{separator_char}' => trim($separator),
            ]);
        }

        return strtr('/(?>(?P<attribute>.+?{colon}[^{colon_char}]+){separator}(?!{separator}(?:[^{colon_char}{separator_char}])+{colon}))/', [
            '{colon}' => $colon,
            '{separator}' => $separator,
            '{colon_char}' => $colonChar,
            '{separator_char}' => trim($separator),
        ]);
    }

    private function getSeparator(?int $shopId): string
    {
        $separator = $this->configuration->getAttributesSeparator($shopId);

        if ('-' === $separator && \Tools::version_compare($this->psVersion, '1.7.8', '>=')) {
            $separator = ' -';
        }

        return $separator . ' ';
    }

    private function getColon(): string
    {
        if (\Tools::version_compare($this->psVersion, '1.7.8')) {
            return ' : ';
        }

        return $this->context->getTranslator()->trans(': ', [], 'Shop.Pdf');
    }
}
