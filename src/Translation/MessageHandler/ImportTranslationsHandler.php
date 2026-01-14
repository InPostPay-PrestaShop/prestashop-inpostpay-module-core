<?php

declare(strict_types=1);

namespace izi\prestashop\Translation\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\Translation\Message\ImportTranslationsCommand;
use izi\prestashop\Translation\Util\TranslationFinder;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Translation;
use PrestaShopBundle\Translation\Loader\DatabaseTranslationLoader;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class ImportTranslationsHandler
{
    use CommandHandlerTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var TranslationFinder
     */
    private $finder;

    public function __construct(EntityManagerInterface $entityManager, LoaderInterface $loader, ?TranslationFinder $finder = null)
    {
        $this->entityManager = $entityManager;
        $this->loader = $loader;
        $this->finder = $finder ?? new TranslationFinder();
    }

    /**
     * @internal
     */
    public static function importModuleTranslations(\Module $module): void
    {
        $container = \is_callable([$module, 'getContainer']) ? $module->getContainer() : SymfonyContainer::getInstance();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        try {
            $loader = $container->get('prestashop.translation.database_loader');
        } catch (ServiceNotFoundException $e) {
            $loader = new DatabaseTranslationLoader($em);
        }

        $handler = new self($em, $loader);
        $command = new ImportTranslationsCommand($module->getLocalPath() . 'translations');

        ($handler)($command);
    }

    public function __invoke(ImportTranslationsCommand $command): void
    {
        $directory = $command->getDirectory();
        $languages = $this->entityManager->getRepository(Lang::class)->findAll();

        foreach ($languages as $language) {
            $this->importTranslations($directory, $language);
        }
    }

    private function importTranslations(string $directory, Lang $language): void
    {
        $catalogue = $this->finder->getCatalogue($directory, $language->getLocale());

        if (null === $catalogue) {
            return;
        }

        $catalogue = $this->filterCatalogue($catalogue);

        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $translation) {
                $translation = $this->createTranslation($id, $translation, $domain, $language);
                $this->entityManager->persist($translation);
            }
        }

        $this->entityManager->flush();
    }

    private function filterCatalogue(MessageCatalogue $fileCatalogue): MessageCatalogue
    {
        $messages = $fileCatalogue->all();
        $locale = $fileCatalogue->getLocale();

        foreach ($fileCatalogue->getDomains() as $domain) {
            $databaseCatalogue = $this->loader->load(null, $locale, $domain)->all($domain);
            $messages[$domain] = array_diff_key($messages[$domain], $databaseCatalogue);

            if ([] === $messages[$domain]) {
                unset($messages[$domain]);
            }
        }

        return new MessageCatalogue($locale, $messages);
    }

    private function createTranslation(string $id, string $translation, string $domain, Lang $language): Translation
    {
        return (new Translation())
            ->setKey($id)
            ->setTranslation($translation)
            ->setDomain($domain)
            ->setLang($language);
    }
}
