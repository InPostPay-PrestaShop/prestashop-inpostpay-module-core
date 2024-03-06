<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

final class PropertyDocBlockTypeExtractor implements PropertyTypeExtractorInterface
{
    /**
     * @var Parser|null
     */
    private $parser;

    private $namespaces = [];
    private $types = [];

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?? (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return Type[]|null
     */
    public function getTypes($class, $property, array $context = []): ?array
    {
        $key = sprintf('%s::%s', $class, $property);

        if (array_key_exists($key, $this->types)) {
            return $this->types[$key];
        }

        return $this->types[$key] = $this->getPropertyTypesFromDocBlock((string) $class, (string) $property);
    }

    private function getPropertyTypesFromDocBlock(string $class, string $property): ?array
    {
        try {
            $reflectionProperty = new \ReflectionProperty($class, $property);
        } catch (\ReflectionException $e) {
            return null;
        }

        if (false === $comment = $reflectionProperty->getDocComment()) {
            return null;
        }

        if (!preg_match('/@var\s+(.+)\s*?\n/', $comment, $matches)) {
            return null;
        }

        try {
            $context = $this->getNamespaceContext($reflectionProperty->getDeclaringClass());
        } catch (\Exception $e) {
            return null;
        }

        if (null === $type = $this->resolveType($matches[1], $context)) {
            return null;
        }

        return is_array($type) ? $type : [$type];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function getNamespaceContext(\ReflectionClass $class): array
    {
        if (array_key_exists($className = $class->getName(), $this->namespaces)) {
            return $this->namespaces[$className];
        }

        $fileName = $class->getFileName();
        $namespace = $class->getNamespaceName();

        return $this->namespaces[$className] = $this->createNamespaceContext($namespace, file_get_contents($fileName));
    }

    private function createNamespaceContext(string $namespace, string $fileContents): array
    {
        $namespace = trim($namespace, '\\');

        $useStatements = [];

        /* Depending on the PS version, the available library version might not be able to parse PHP >=7.1 syntax.
        To circumvent that, we try to limit parsing to the code preceding the first class declaration found in the file. */
        if (preg_match('/\n(?:(?:final|abstract)\s+)?class\s+/', $fileContents, $matches, PREG_OFFSET_CAPTURE)) {
            $fileContents = substr($fileContents, 0, (int) $matches[0][1]);
        }

        foreach ($this->parser->parse($fileContents) as $node) {
            if (!$node instanceof Namespace_ || $namespace !== (string) $node->name) {
                continue;
            }

            $useStatements[] = $this->extractUseStatements($node);
        }

        $useStatements = array_merge(...$useStatements);

        return [$namespace, $useStatements];
    }

    private function extractUseStatements(Namespace_ $namespace): array
    {
        $useStatements = [];

        foreach ($namespace->stmts as $node) {
            if (!$node instanceof Use_) {
                continue;
            }

            foreach ($node->uses as $use) {
                if (null === $use->alias) {
                    $alias = $use->name->getLast();
                } elseif (is_string($use->alias)) {
                    $alias = $use->alias;
                } else {
                    $alias = $use->alias->name;
                }

                $useStatements[$alias] = (string) $use->name;
            }
        }

        return $useStatements;
    }

    /**
     * @return Type[]|Type|null
     */
    private function resolveType(string $type, array $context, bool $nullable = false)
    {
        if ('mixed' === $type) {
            return null;
        }

        if (in_array($type, Type::$builtinTypes)) {
            return new Type($type, $nullable);
        }

        if (false !== strpos($type, '|')) {
            return $this->resolveCompoundType($type, $context);
        }

        if ('[]' === substr($type, -2)) {
            return $this->resolveCollectionType($type, $context, $nullable);
        }

        $className = $this->resolveClassName($type, $context);

        if (!class_exists($className)) {
            return null;
        }

        return new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, $className);
    }

    /**
     * @return Type[]
     */
    private function resolveCompoundType(string $value, array $context): array
    {
        $types = explode('|', $value);
        $nullable = in_array(Type::BUILTIN_TYPE_NULL, $types, true);

        $result = [];

        foreach ($types as $type) {
            if (null === $type = $this->resolveType($type, $context, $nullable)) {
                continue;
            }

            $result[] = $type;
        }

        return $result;
    }

    private function resolveCollectionType(string $type, array $context, bool $nullable): Type
    {
        if ('mixed[]' === $type) {
            $collectionKeyType = null;
            $collectionValueType = null;
        } else {
            $collectionKeyType = new Type(Type::BUILTIN_TYPE_INT);
            $collectionValueType = $this->resolveType(substr($type, 0, -2), $context);
        }

        return new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true, $collectionKeyType, $collectionValueType);
    }

    private function resolveClassName(string $type, array $context): string
    {
        if (0 === strpos($type, '\\')) {
            return substr($type, 1);
        }

        [$namespace, $aliases] = $context;
        $parts = explode('\\', $type, 2);

        if (isset($aliases[$parts[0]])) {
            $parts[0] = $aliases[$parts[0]];

            return implode('\\', $parts);
        }

        return '' === $namespace
            ? $type
            : sprintf('%s\\%s', $namespace, $type);
    }
}
