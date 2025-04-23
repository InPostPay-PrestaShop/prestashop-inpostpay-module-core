<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Product;

use izi\prestashop\Form\DataTransformer\CombinationToAttributeIdsTransformer;
use izi\prestashop\Form\DataTransformer\ObjectModelToIdTransformer;
use izi\prestashop\Form\Type\ObjectModelType;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\CombinationRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\Product\ProductAttribute;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CombinationByAttributesChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'combinationbyattributeschoicetype';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var CombinationRepository
     */
    private $repository;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(ObjectManagerInterface $manager, \Context $context, LegacyTranslator $translator)
    {
        $this->manager = $manager;
        $this->context = $context;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $repository = $this->getRepository();

        $attributeClass = $this->repository->getAttributeModelClass();
        $attributesByGroupId = $repository->getAvailableAttributesByProductId(
            $options['product_id'],
            $options['language_id']
        );

        // TODO: we currently do not take into account that not all product attribute combinations are possible
        /** @var ProductAttribute[] $attributes */
        foreach ($attributesByGroupId as $groupId => $attributes) {
            $groupName = $attributes[0]->getGroup()->name;

            $builder->add((string) $groupId, ObjectModelType::class, [
                'class' => $attributeClass,
                'input' => 'id',
                'label' => $groupName,
                'choices' => array_map(static function (ProductAttribute $attribute) {
                    return $attribute->getAttribute();
                }, $attributes),
            ]);
        }

        $builder->addModelTransformer($this->createModelTransformer($repository, $options));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'product_id',
            ])
            ->setDefaults([
                'language_id' => (int) $this->context->language->id,
                'input' => 'object',
                'error_bubbling' => false,
                'invalid_message' => $this->translator->l('Product combination with selected attributes does not exist.', self::TRANSLATION_SOURCE),
                'empty_data' => static function (FormInterface $form): ?array {
                    $data = array_map(static function (FormInterface $child) {
                        return $child->getData();
                    }, $form->all());

                    $data = array_filter($data, static function ($attributeId): bool {
                        return null !== $attributeId;
                    });

                    if ([] === $data) {
                        return null;
                    }

                    return $data;
                },
            ])
            ->setAllowedTypes('product_id', 'int')
            ->setAllowedTypes('language_id', 'int')
            ->setAllowedValues('input', ['object', 'id']);
    }

    private function createModelTransformer(ObjectRepositoryInterface $repository, array $options): DataTransformerInterface
    {
        $transformer = new CombinationToAttributeIdsTransformer($repository, $options['product_id']);

        if ('id' !== $options['input']) {
            return $transformer;
        }

        $modelToIdTransformer = new ObjectModelToIdTransformer($this->manager, \Combination::class, $options['language_id']);

        return new DataTransformerChain([
            new ReversedTransformer($modelToIdTransformer),
            $transformer,
        ]);
    }

    /**
     * @return CombinationRepository
     */
    private function getRepository(): ObjectRepositoryInterface
    {
        return $this->repository ?? ($this->repository = $this->manager->getRepository(\Combination::class));
    }
}
