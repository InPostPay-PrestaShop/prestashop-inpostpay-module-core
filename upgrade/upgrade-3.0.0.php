<?php

use InPost\Izi\Upgrade\FileRemoverTrait;
use InPost\Izi\Upgrade\TranslationImporterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/FileRemoverTrait.php';
require_once __DIR__ . '/TranslationImporterTrait.php';

class InPostIziUpdater_3_0_0
{
    use FileRemoverTrait;
    use TranslationImporterTrait;

    private const CLASSES_TO_REMOVE = [
        izi\prestashop\AdminKernel::class,
        izi\prestashop\Configuration\Initializer\TwigConfigInitializer::class,
        izi\prestashop\Controller\Admin\AbstractController::class,
        izi\prestashop\DependencyInjection\ContainerBuilder::class,
        izi\prestashop\DependencyInjection\TypedReference::class,
        izi\prestashop\DependencyInjection\Argument\ServiceClosureArgument::class,
        izi\prestashop\DependencyInjection\Compiler\AnalyzeServiceReferencesPass::class,
        izi\prestashop\DependencyInjection\Compiler\ProvideServiceLocatorFactoriesPass::class,
        izi\prestashop\DependencyInjection\Compiler\TaggedIteratorsCollectorPass::class,
        izi\prestashop\DependencyInjection\Dumper\PhpDumper::class,
        izi\prestashop\Form\FormFactoryFactory::class,
        izi\prestashop\Form\ChoiceList\OrderStateChoiceLoader::class,
        izi\prestashop\Form\ChoiceList\ProductImageTypeChoiceLoader::class,
        izi\prestashop\Form\Extension\DependencyInjectionExtension::class,
        izi\prestashop\Form\Type\EnvironmentChoiceType::class,
        izi\prestashop\Form\Type\OrderStateChoiceType::class,
        izi\prestashop\Form\Type\SwitchType::class,
        izi\prestashop\Form\Type\TranslatableType::class,
        izi\prestashop\Form\Type\Compatibility\CategoryChoiceTreeType::class,
        izi\prestashop\Form\Type\Consent\ConsentRequirementChoiceType::class,
        izi\prestashop\Form\Type\Shipping\WeekDayChoiceType::class,
        izi\prestashop\Form\Type\Widget\WidgetFrameStyleChoiceType::class,
        izi\prestashop\Form\Type\Widget\WidgetSizeChoiceType::class,
        izi\prestashop\Form\Type\Widget\WidgetVariantChoiceType::class,
        izi\prestashop\Form\TypeExtension\HelpTextExtension::class,
        izi\prestashop\Form\TypeExtension\ChoicesAsValuesTypeExtension::class,
        izi\prestashop\Hook\Exception\HookExceptionTrait::class,
        izi\prestashop\Hook\Legacy\Admin\Product\DisplayAdminProductsExtra::class,
        izi\prestashop\Hook\Legacy\Admin\Product\ProductOptionsFormRenderer::class,
        izi\prestashop\Http\Response\EventStreamResponse::class,
        izi\prestashop\Http\Response\ServerSentEvent::class,
        izi\prestashop\Http\Response\ServerSentEventBuilder::class,
        izi\prestashop\HttpKernel\ServiceParamConverter::class,
        izi\prestashop\Repository\CartRuleRepository::class,
        izi\prestashop\Repository\CartRuleRepositoryInterface::class,
        izi\prestashop\Routing\AdminUrlGenerator::class,
        izi\prestashop\Routing\AnnotationDirectoryLoader::class,
        izi\prestashop\Security\EmployeeAuthenticator::class,
        izi\prestashop\Security\LazyUserProvider::class,
        izi\prestashop\Serializer\Exception\MissingConstructorArgumentsException::class,
        izi\prestashop\Serializer\Normalizer\DateTimeNormalizer::class,
        izi\prestashop\Serializer\Normalizer\JsonSerializableNormalizer::class,
        izi\prestashop\Serializer\Normalizer\ObjectNormalizer::class,
        izi\prestashop\Translation\DomainNormalizingTranslator::class,
        izi\prestashop\Translation\LegacyTranslator::class,
        izi\prestashop\Translation\PaymentTypeTranslator::class,
        izi\prestashop\Translation\ServiceNameTranslator::class,
        izi\prestashop\Twig\Extension\LegacyTranslationExtension::class,
        izi\prestashop\Twig\Loader\TemplateNameMappingLoader::class,
        izi\prestashop\Validator\ConstraintValidatorFactory::class,
        izi\prestashop\Validator\ValidatorFactory::class,
        izi\prestashop\View\Asset\VersionStrategy\JsonManifestVersionStrategy::class,
    ];

    private const FILES_TO_REMOVE = [
        'config/services/common_admin.yml',
        'config/services/common_front.yml',
        'config/services/sf28.yml',
        'config/services/sf34.yml',
        'src/Resources/',
        'upgrade/CacheClearer.php',
        'views/css/admin/admin-legacy.css',
        'views/img/admin/banner_admin.png',
        'views/templates/admin/admin_template_translations.tpl',
        'views/templates/admin/config/shipping/', // v1.5 templates with "legacy_trans" filter usages
        'views/templates/hook/admin/cart_rule_form.tpl',
        'views/templates/hook/admin/order_details.tpl',
        'views/templates/hook/legacy/admin/order_details.tpl',
        'views/templates/hook/legacy/admin/product/_form.tpl',
        'views/templates/hook/legacy/admin/product/options_form.tpl',
        'views/templates/hook/legacy/admin/product/options_form_tab.tpl',
    ];

    public function __construct(Module $module, string $psVersion = _PS_VERSION_)
    {
        $this->module = $module;
        $this->psVersion = $psVersion;
    }

    public static function create(Module $module): self
    {
        return new self($module);
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->removeClasses(self::CLASSES_TO_REMOVE)
            && $this->removeFiles(self::FILES_TO_REMOVE)
            && $this->importTranslations();
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_3_0_0(Module $module): bool
{
    if (Tools::version_compare(_PS_VERSION_, '1.7.6')) {
        try {
            $module->uninstall();
        } finally {
            return false;
        }
    }

    return InPostIziUpdater_3_0_0::create($module)->upgrade();
}
