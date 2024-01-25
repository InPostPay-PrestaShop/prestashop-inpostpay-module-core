<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

final class Hydrator implements HydratorInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws \PrestaShopException
     */
    public function hydrate(array $data, string $class, \ObjectModel $model = null, int $languageId = null): \ObjectModel
    {
        if ([] === $data) {
            throw new \DomainException('Provided data is empty.');
        }

        if (!is_int(key($data))) {
            $data = [$data];
        }

        if (null !== $model) {
            return $this->hydrateObject($model, $data, $languageId);
        }

        $collection = $this->hydrateCollection($data, $class, $languageId);
        if (1 !== $count = count($collection)) {
            throw new \DomainException(sprintf('Unexpected collection count. Expected: 1, got: %d.', $count));
        }

        return current($collection);
    }

    /**
     * @template T of \ObjectModel
     *
     * @param class-string<T> $class
     *
     * @return T[]
     *
     * @throws \PrestaShopException
     */
    public function hydrateCollection(array $data, string $class, int $languageId = null): array
    {
        return $class::hydrateCollection($class, $data, $languageId);
    }

    private function hydrateObject(\ObjectModel $model, array $data, int $languageId = null): \ObjectModel
    {
        $metadata = $model::getDefinition(get_class($model));
        $data = $this->normalizeData($data, $metadata);

        $id = (int) $model->id;
        $idColumn = $metadata['primary'];

        if (0 < $id && isset($data[$idColumn]) && $id !== (int) $data[$idColumn]) {
            throw new \DomainException('Identifier mismatch.');
        }

        $model->hydrate($data, $languageId);

        return $model;
    }

    private function normalizeData(array $data, array $metadata): array
    {
        $result = [];

        $langFields = empty($metadata['multilang']) ? [] : array_filter($metadata['fields'], static function (array $field) {
            return !empty($field['lang']);
        });

        foreach ($data as $row) {
            $result = $this->appendDataRow($row, $langFields, $result);
        }

        return $result;
    }

    private function appendDataRow(array $row, array $langFields, array $result): array
    {
        foreach ($row as $field => $value) {
            if (!isset($langFields[$field])) {
                $result[$field] = $value;
            } elseif (!isset($row['id_lang'])) {
                throw new \DomainException('Language identifier is missing.');
            } else {
                $result[$field][(int) $row['id_lang']] = $value;
            }
        }

        return $result;
    }
}
