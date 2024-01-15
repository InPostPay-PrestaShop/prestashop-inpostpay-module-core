<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer\Normalizer;

use izi\prestashop\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as BaseNormalizer;

/**
 * Denormalizes nested objects on Sf 2.8.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @see \Symfony\Component\Serializer\Normalizer\AbstractNormalizer
 * @see \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer
 * @see \Symfony\Component\Serializer\Normalizer\ObjectNormalizer
 */
final class ObjectNormalizer extends BaseNormalizer
{
    /**
     * @var PropertyTypeExtractorInterface|null
     */
    private $propertyTypeExtractor;

    private $typesCache = [];

    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null, PropertyAccessorInterface $propertyAccessor = null, PropertyTypeExtractorInterface $propertyTypeExtractor = null)
    {
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor);
        $this->propertyTypeExtractor = $propertyTypeExtractor;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }
        $allowedAttributes = $this->getAllowedAttributes($class, $context, true);
        $normalizedData = $this->prepareForDenormalization($data);

        $reflectionClass = new \ReflectionClass($class);
        $object = $this->instantiateObject($normalizedData, $class, $context, $reflectionClass, $allowedAttributes, $format);

        foreach ($normalizedData as $attribute => $value) {
            if ($this->nameConverter) {
                $attribute = $this->nameConverter->denormalize($attribute);
            }

            $allowed = $allowedAttributes === false || in_array($attribute, $allowedAttributes);
            $ignored = in_array($attribute, $this->ignoredAttributes);

            if ($allowed && !$ignored) {
                try {
                    $this->propertyAccessor->setValue($object, $attribute, $value);
                } catch (NoSuchPropertyException $exception) {
                    // Properties not found are ignored
                }
            }
        }

        return $object;
    }

    protected function instantiateObject(array &$data, $class, array &$context, \ReflectionClass $reflectionClass, $allowedAttributes, string $format = null)
    {
        if (!$constructor = $reflectionClass->getConstructor()) {
            return new $class();
        }

        if (!$constructor->isPublic()) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        $constructorParameters = $constructor->getParameters();

        $params = [];
        foreach ($constructorParameters as $constructorParameter) {
            $paramName = $constructorParameter->name;
            $key = $this->nameConverter ? $this->nameConverter->normalize($paramName) : $paramName;

            $allowed = false === $allowedAttributes || in_array($paramName, $allowedAttributes);
            if ($constructorParameter->isVariadic()) {
                if ($allowed && (isset($data[$key]) || array_key_exists($key, $data))) {
                    if (!is_array($data[$paramName])) {
                        throw new RuntimeException(sprintf('Cannot create an instance of "%s" from serialized data because the variadic parameter "%s" can only accept an array.', $class, $constructorParameter->name));
                    }

                    $variadicParameters = [];
                    foreach ($data[$paramName] as $parameterData) {
                        $variadicParameters[] = $this->denormalizeParameter($reflectionClass, $constructorParameter, $paramName, $parameterData, $context, $format);
                    }

                    $params = array_merge($params, $variadicParameters);
                    unset($data[$key]);
                }
            } elseif ($allowed && (isset($data[$key]) || array_key_exists($key, $data))) {
                $parameterData = $data[$key];
                if (null === $parameterData && $constructorParameter->allowsNull()) {
                    $params[] = null;
                    // Don't run set for a parameter passed to the constructor
                    unset($data[$key]);
                    continue;
                }

                // Don't run set for a parameter passed to the constructor
                $params[] = $this->denormalizeParameter($reflectionClass, $constructorParameter, $paramName, $parameterData, $context, $format);
                unset($data[$key]);
            } elseif ($constructorParameter->isDefaultValueAvailable()) {
                $params[] = $constructorParameter->getDefaultValue();
            } elseif ($constructorParameter->hasType() && $constructorParameter->getType()->allowsNull()) {
                $params[] = null;
            } else {
                throw new MissingConstructorArgumentsException(sprintf('Cannot create an instance of "%s" from serialized data because its constructor requires parameter "%s" to be present.', $class, $constructorParameter->name));
            }
        }

        return $constructor->isConstructor()
            ? $reflectionClass->newInstanceArgs($params)
            : $constructor->invokeArgs(null, $params);
    }

    protected function denormalizeParameter(\ReflectionClass $class, \ReflectionParameter $parameter, $parameterName, $parameterData, array $context, $format = null)
    {
        if (null !== $this->propertyTypeExtractor && !$parameter->isVariadic() && null !== $this->propertyTypeExtractor->getTypes($class->getName(), $parameterName)) {
            return $this->validateAndDenormalize($class->getName(), $parameterName, $parameterData, $format, $context);
        }

        try {
            if (($parameterType = $parameter->getType()) instanceof \ReflectionNamedType && !$parameterType->isBuiltin()) {
                $parameterClass = $parameterType->getName();
                new \ReflectionClass($parameterClass); // throws a \ReflectionException if the class doesn't exist

                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException(sprintf('Cannot create an instance of "%s" from serialized data because the serializer inject in "%s" is not a denormalizer.', $parameterClass, self::class));
                }

                $parameterData = $this->serializer->denormalize($parameterData, $parameterClass, $format, $this->createChildContext($context, $format));
            }
        } catch (\ReflectionException $e) {
            throw new RuntimeException(sprintf('Could not determine the class of the parameter "%s".', $parameterName), 0, $e);
        } catch (MissingConstructorArgumentsException $e) {
            if (!$parameter->getType()->allowsNull()) {
                throw $e;
            }

            return null;
        }

        return $parameterData;
    }

    protected function createChildContext(array $parentContext, $attribute, string $format = null): array
    {
        $context = $parentContext;
        $context['cache_key'] = $this->getCacheKey($format, $context);

        return $context;
    }

    private function getCacheKey(?string $format, array $context)
    {
        unset($context['cache_key']);

        try {
            return md5($format . serialize($context));
        } catch (\Exception $e) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }

    private function validateAndDenormalize(string $currentClass, string $attribute, $data, ?string $format, array $context)
    {
        if (null === $types = $this->getTypes($currentClass, $attribute)) {
            return $data;
        }

        $expectedTypes = [];
        $isUnionType = \count($types) > 1;
        $missingConstructorArgumentException = null;
        foreach ($types as $type) {
            if (null === $data && $type->isNullable()) {
                return null;
            }

            $collectionValueType = $type->isCollection() ? $type->getCollectionValueType() : null;

            // Fix a collection that contains the only one element
            // This is special to xml format only
            if ('xml' === $format && null !== $collectionValueType && (!is_array($data) || !is_int(key($data)))) {
                $data = [$data];
            }

            if ('xml' === $format && '' === $data && Type::BUILTIN_TYPE_ARRAY === $type->getBuiltinType()) {
                return [];
            }

            if (null !== $collectionValueType && Type::BUILTIN_TYPE_OBJECT === $collectionValueType->getBuiltinType()) {
                $builtinType = Type::BUILTIN_TYPE_OBJECT;
                $class = $collectionValueType->getClassName() . '[]';

                if (null !== $collectionKeyType = $type->getCollectionKeyType()) {
                    $context['key_type'] = $collectionKeyType;
                }
            } elseif ($type->isCollection() && null !== ($collectionValueType = $type->getCollectionValueType()) && Type::BUILTIN_TYPE_ARRAY === $collectionValueType->getBuiltinType()) {
                // get inner type for any nested array
                $innerType = $collectionValueType;

                // note that it will break for any other builtinType
                $dimensions = '[]';
                while (null !== $innerType->getCollectionValueType() && Type::BUILTIN_TYPE_ARRAY === $innerType->getBuiltinType()) {
                    $dimensions .= '[]';
                    $innerType = $innerType->getCollectionValueType();
                }

                if (null !== $innerType->getClassName()) {
                    // the builtinType is the inner one and the class is the class followed by []...[]
                    $builtinType = $innerType->getBuiltinType();
                    $class = $innerType->getClassName() . $dimensions;
                } else {
                    // default fallback (keep it as array)
                    $builtinType = $type->getBuiltinType();
                    $class = $type->getClassName();
                }
            } else {
                $builtinType = $type->getBuiltinType();
                $class = $type->getClassName();
            }

            $expectedTypes[Type::BUILTIN_TYPE_OBJECT === $builtinType && $class ? $class : $builtinType] = true;

            // This try-catch should cover all NotNormalizableValueException (and all return branches after the first
            // exception) so we could try denormalizing all types of a union type. If the target type is not an union
            // type, we will just re-throw the caught exception.
            // In the case of no denormalization succeeds with a union type, it will fall back to the default exception
            // with the acceptable types list.
            try {
                if (Type::BUILTIN_TYPE_OBJECT === $builtinType) {
                    if (!$this->serializer instanceof DenormalizerInterface) {
                        throw new LogicException(sprintf('Cannot denormalize attribute "%s" for class "%s" because injected serializer is not a denormalizer.', $attribute, $class));
                    }

                    $childContext = $this->createChildContext($context, $format);
                    if ($this->serializer->supportsDenormalization($data, $class, $format)) {
                        return $this->serializer->denormalize($data, $class, $format, $childContext);
                    }
                }

                // JSON only has a Number type corresponding to both int and float PHP types.
                // PHP's json_encode, JavaScript's JSON.stringify, Go's json.Marshal as well as most other JSON encoders convert
                // floating-point numbers like 12.0 to 12 (the decimal part is dropped when possible).
                // PHP's json_decode automatically converts Numbers without a decimal part to integers.
                // To circumvent this behavior, integers are converted to floats when denormalizing JSON based formats and when
                // a float is expected.
                if (Type::BUILTIN_TYPE_FLOAT === $builtinType && is_int($data) && null !== $format && false !== strpos($format, 'json')) {
                    return (float) $data;
                }

                if ('false' === $builtinType && false === $data) {
                    return false;
                }

                if (('is_' . $builtinType)($data)) {
                    return $data;
                }
            } catch (MissingConstructorArgumentsException $e) {
                if (!$isUnionType) {
                    throw $e;
                }

                if (!$missingConstructorArgumentException) {
                    $missingConstructorArgumentException = $e;
                }
            }
        }

        if ($missingConstructorArgumentException) {
            throw $missingConstructorArgumentException;
        }

        throw new RuntimeException(sprintf('The type of the "%s" attribute for class "%s" must be one of "%s" ("%s" given).', $attribute, $currentClass, implode('", "', array_keys($expectedTypes)), gettype($data)));
    }

    private function getTypes(string $currentClass, string $attribute): ?array
    {
        if (null === $this->propertyTypeExtractor) {
            return null;
        }

        $key = $currentClass . '::' . $attribute;
        if (isset($this->typesCache[$key])) {
            return false === $this->typesCache[$key] ? null : $this->typesCache[$key];
        }

        if (null !== $types = $this->propertyTypeExtractor->getTypes($currentClass, $attribute)) {
            return $this->typesCache[$key] = $types;
        }

        $this->typesCache[$key] = false;

        return null;
    }
}
