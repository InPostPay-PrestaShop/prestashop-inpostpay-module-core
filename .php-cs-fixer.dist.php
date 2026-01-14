<?php

require_once __DIR__ . '/vendor/autoload.php';

$config = new class extends PrestaShop\CodingStandards\CsFixer\Config {
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'native_constant_invocation' => ['scope' => 'namespaced'],
            'native_function_invocation' => [
                'include' => ['@compiler_optimized'],
                'scope' => 'namespaced',
                'strict' => true,
            ],
            'trailing_comma_in_multiline' => [
                'after_heredoc' => true,
                'elements' => ['array_destructuring', 'arrays', 'match'],
            ],
            'phpdoc_separation' => [
                'groups' => [
                    ['Annotation', 'NamedArgumentConstructor', 'Target'],
                    ['author', 'copyright', 'license'],
                    ['category', 'package', 'subpackage'],
                    ['property', 'property-read', 'property-write'],
                    ['deprecated', 'link', 'see', 'since'],
                    ['ORM\\*'],
                    ['Assert\\*'],
                ],
            ],
        ]);
    }
};

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = $config->setUsingCache(true)->getFinder();
$finder->in(__DIR__)->exclude([
    'vendor',
    'node_modules',
]);

return $config;
