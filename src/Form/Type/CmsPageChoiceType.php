<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CmsPageChoiceType extends AbstractType
{
    /**
     * @var Context
     */
    private $shopContext;

    public function __construct(Context $shopContext)
    {
        $this->shopContext = $shopContext;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \CMS::class,
            'query_builder' => function (ObjectRepositoryInterface $repository, int $languageId, ?int $shopId) {
                return $repository->createQueryBuilder('c', $languageId, $shopId)
                    ->where('c.active = 1')
                    ->orderBy('c.position ASC');
            },
            'choice_label' => 'meta_title',
            'shop_id' => $this->shopContext->isSingleShopContext() ? $this->shopContext->getContextShopID() : null,
        ]);
    }

    public function getParent(): string
    {
        return ObjectModelType::class;
    }
}
