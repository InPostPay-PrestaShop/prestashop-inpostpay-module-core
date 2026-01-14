<?php

declare(strict_types=1);

namespace izi\prestashop\ProductOptions;

use izi\prestashop\Database\Connection;
use izi\prestashop\Product\Image\ImageGalleryType;

class ProductOptionsRepository implements ProductOptionsRepositoryInterface
{
    public const TABLE_NAME = 'inpostizi_product_options';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array<int, ProductOptions> options by product ID
     */
    private $options = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @internal
     */
    public static function create(): self
    {
        return new self(new Connection(\Db::getInstance()));
    }

    public function add(ProductOptions $options): void
    {
        $data = $this->mapTableData($options);

        try {
            $this->connection->insert(self::TABLE_NAME, $data);
        } catch (\PrestaShopDatabaseException $e) {
            if (1062 !== $e->getCode()) {
                throw $e;
            }

            throw new \DomainException('Product options already exist.');
        }

        $this->options[$options->getProductId()] = $options;
    }

    public function find(int $productId): ?ProductOptions
    {
        if (0 >= $productId) {
            return null;
        }

        if (\array_key_exists($productId, $this->options)) {
            return $this->options[$productId];
        }

        $qb = $this->createQueryBuilder()->where('product_id = ' . $productId);

        return $this->options[$productId] = $this->getOneOrNullResult($qb);
    }

    public function findByProductIds(int ...$productIds): array
    {
        if ([] === $productIds) {
            return [];
        }

        $qb = $this->createQueryBuilder()->where('product_id IN (' . implode(',', $productIds) . ')');

        return $this->getResult($qb, 'product_id');
    }

    public function update(ProductOptions $options): void
    {
        $data = $this->mapTableData($options);
        unset($data['product_id']);

        $this->connection->update(self::TABLE_NAME, $data, ['product_id' => $options->getProductId()]);
    }

    protected function createQueryBuilder(): \DbQuery
    {
        return (new \DbQuery())->from(self::TABLE_NAME);
    }

    protected function getOneOrNullResult(\DbQuery $qb): ?ProductOptions
    {
        if (false === $row = $this->connection->fetchAssociative((string) $qb)) {
            return null;
        }

        return $this->hydrate($row);
    }

    protected function getResult(\DbQuery $qb, ?string $indexBy = null): array
    {
        $data = $this->connection->fetchAllAssociative((string) $qb);
        if (null !== $indexBy) {
            $data = array_column($data, null, $indexBy);
        }

        return array_map([$this, 'hydrate'], $data);
    }

    protected function hydrate(array $row): ProductOptions
    {
        $options = new ProductOptions((int) $row['product_id']);

        if (null !== $row['image_gallery_type']) {
            $options->setImageGalleryType(ImageGalleryType::tryFrom((int) $row['image_gallery_type']));
        }

        return $options;
    }

    private function mapTableData(ProductOptions $options): array
    {
        $galleryType = $options->getImageGalleryType();

        return [
            'product_id' => $options->getProductId(),
            'image_gallery_type' => null === $galleryType ? null : $galleryType->value,
        ];
    }
}
