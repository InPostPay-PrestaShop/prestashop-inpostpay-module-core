<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

use izi\prestashop\Configuration\ProductConfigurationInterface;
use Psr\Log\LoggerInterface;

final class ProductDescriptionProvider implements ProductDescriptionProviderInterface
{
    private const VISIBLE_CHARACTER = '~[^\s\x00\p{Z}\x{AD}\x{200B}-\x{200D}\x{2060}\x{FEFF}]~u';

    /**
     * @var ProductConfigurationInterface
     */
    private $productConfiguration;

    /**
     * @var DescriptionSanitizerInterface
     */
    private $sanitizer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ProductConfigurationInterface $productConfiguration, DescriptionSanitizerInterface $sanitizer, LoggerInterface $logger)
    {
        $this->productConfiguration = $productConfiguration;
        $this->sanitizer = $sanitizer;
        $this->logger = $logger;
    }

    public function getDescription(\Product $product, ?int $shopId = null): string
    {
        $source = $this->productConfiguration->getDescriptionSource($shopId);

        if (DescriptionSource::ShortOnly() === $source) {
            return $this->format($product, (string) $product->description_short);
        }

        $description = $this->format($product, (string) $product->description);

        if ('' !== $description || DescriptionSource::LongOnly() === $source) {
            return $description;
        }

        return $this->format($product, (string) $product->description_short);
    }

    private function format(\Product $product, string $html): string
    {
        try {
            $html = $this->sanitizer->sanitize($html);

            return $this->hasVisibleText($html) ? $html : '';
        } catch (\Throwable $e) {
            $this->logger->error('Could not sanitize product description.', [
                'exception' => $e,
                'product_id' => (int) $product->id,
            ]);

            return '';
        }
    }

    private function hasVisibleText(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        return 1 === preg_match(self::VISIBLE_CHARACTER, $text);
    }
}
