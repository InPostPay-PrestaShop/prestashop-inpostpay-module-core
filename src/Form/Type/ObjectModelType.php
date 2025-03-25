<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\ChoiceList\ObjectModelChoiceLoader;
use izi\prestashop\Form\DataTransformer\ObjectModelCollectionToIdsTransformer;
use izi\prestashop\Form\DataTransformer\ObjectModelToIdTransformer;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Inspired by {@see \Symfony\Bridge\Doctrine\Form\Type\EntityType}.
 */
final class ObjectModelType extends AbstractType
{
    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var array<string, ObjectModelChoiceLoader>
     */
    private $choiceLoaders = [];

    public function __construct(ObjectManagerInterface $manager, \Context $context)
    {
        $this->context = $context;
        $this->manager = $manager;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ('id' !== $options['input']) {
            return;
        }

        if ($options['multiple']) {
            $builder->addModelTransformer(new ReversedTransformer(
                new ObjectModelCollectionToIdsTransformer(
                    $this->manager,
                    $options['class'],
                    $options['language_id'],
                    $options['shop_id']
                )
            ));
        } else {
            $builder->addModelTransformer(new ReversedTransformer(
                new ObjectModelToIdTransformer(
                    $this->manager,
                    $options['class'],
                    $options['language_id'],
                    $options['shop_id']
                )
            ));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['class'])
            ->setDefaults([
                'language_id' => (int) $this->context->language->id,
                'shop_id' => null,
                'input' => 'object',
                'query_builder' => null,
                'choices' => null,
                'choice_loader' => function (Options $options): ?ChoiceLoaderInterface {
                    if (null !== $options['choices']) {
                        return null;
                    }

                    $cacheKey = $this->getChoiceLoaderCacheKey($options['class'], $options['query_builder'], $options['language_id'], $options['shop_id']);

                    return $this->choiceLoaders[$cacheKey] ?? $this->choiceLoaders[$cacheKey] = new ObjectModelChoiceLoader(
                        $this->manager,
                        $options['class'],
                        $options['query_builder'],
                        $options['language_id'],
                        $options['shop_id']
                    );
                },
                'choice_label' => function (\ObjectModel $model): string {
                    if (is_callable([$model, '__toString'])) {
                        return (string) $model;
                    }

                    // might as well use model's "name" property by default...
                    if (property_exists($model, 'name')) {
                        if (!is_array($model->name)) {
                            return $model->name;
                        }

                        return $model->name[$this->context->language->id] ?? (string) current($model->name);
                    }

                    return sprintf('"%s" #%d', get_class($model), $model->id);
                },
                'choice_name' => static function (\ObjectModel $model): string {
                    return (string) $model->id;
                },
                'choice_value' => static function (?\ObjectModel $model): string {
                    return null !== $model ? (string) $model->id : '';
                },
            ])
            ->setAllowedTypes('language_id', ['null', 'int'])
            ->setAllowedTypes('shop_id', ['null', 'int'])
            ->setAllowedTypes('query_builder', ['null', 'callable', QueryBuilder::class])
            ->setAllowedValues('input', ['object', 'id'])
            ->setNormalizer('query_builder', function (Options $options, $value): ?QueryBuilder {
                if (is_callable($value)) {
                    $value = $value(
                        $this->manager->getRepository($options['class']),
                        $options['language_id'],
                        $options['shop_id']
                    );

                    if (null !== $value && !$value instanceof QueryBuilder) {
                        throw new UnexpectedTypeException($value, QueryBuilder::class);
                    }
                }

                return $value;
            });
    }

    private function getChoiceLoaderCacheKey(string $class, ?QueryBuilder $queryBuilder, ?int $languageId, ?int $shopId): string
    {
        if (null === $queryBuilder) {
            return sprintf('%s_%d_%d', $class, (int) $languageId, (int) $shopId);
        }

        $sql = $queryBuilder->build()->getSql();

        return hash('md5', $sql . (int) $languageId);
    }
}
