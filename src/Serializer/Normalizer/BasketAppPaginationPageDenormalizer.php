<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer\Normalizer;

use izi\prestashop\BasketApp\PaginationPage;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class BasketAppPaginationPageDenormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    public const ITEM_TYPE_KEY = 'inpost_izi_page_item_type';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function denormalize($data, $type, $format = null, array $context = []): ?PaginationPage
    {
        if (null === $data) {
            return null;
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)));
        }

        if (PaginationPage::class !== $type) {
            throw new InvalidArgumentException(sprintf('Unsupported type: "%s".', $type));
        }

        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new LogicException('Cannot denormalize object because injected serializer is not a denormalizer.');
        }

        if ($itemType = $context[self::ITEM_TYPE_KEY] ?? null) {
            $data['content'] = $this->serializer->denormalize($data['content'], $itemType . '[]', $format, $context);
        }

        return new PaginationPage(
            $data['content'],
            (int) $data['total_items'],
            (int) $data['page_index'],
            (int) $data['page_size']
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return PaginationPage::class === $type;
    }
}
