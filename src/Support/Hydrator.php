<?php

namespace Elqora\Dgp\Support;

use ReflectionClass;
use ReflectionNamedType;
use InvalidArgumentException;

final class Hydrator
{
    /**
     * Hydrate an array into an instance of a class.
     *
     * @template T of object
     * @param class-string<T> $class
     * @param mixed $data
     * @return T
     */
    public static function hydrate(string $class, mixed $data): object
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist.");
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $params = $constructor->getParameters();

        // Handle single-parameter constructor with scalar or non-key matching data
        if (count($params) === 1) {
            $paramName = $params[0]->getName();
            $snakeParamName = self::camelToSnake($paramName);

            if (!is_array($data)) {
                $data = [$paramName => $data];
            } elseif (!array_key_exists($paramName, $data) && !array_key_exists($snakeParamName, $data)) {
                if (count($data) === 1 && array_key_exists(0, $data)) {
                    $data = [$paramName => $data[0]];
                } else {
                    $data = [$paramName => $data];
                }
            }
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException("Data for class {$class} must be an array or map to a single constructor parameter.");
        }

        $docComment = $constructor->getDocComment() ?: '';
        $arguments = [];

        foreach ($params as $parameter) {
            $name = $parameter->getName();
            $snakeName = self::camelToSnake($name);

            // Find matching key in data
            $value = null;
            $hasKey = false;

            if (array_key_exists($name, $data)) {
                $value = $data[$name];
                $hasKey = true;
            } elseif (array_key_exists($snakeName, $data)) {
                $value = $data[$snakeName];
                $hasKey = true;
            }

            if (!$hasKey) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }
                if ($parameter->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }
                throw new InvalidArgumentException("Missing required parameter '{$name}' (or '{$snakeName}') for class {$class}.");
            }

            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string<object> $typeName */
                $typeName = $type->getName();
                if ($value === null && $parameter->allowsNull()) {
                    $arguments[] = null;
                } else {
                    $arguments[] = self::hydrate($typeName, $value);
                }
                continue;
            }

            // Check if collection parameter
            if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && is_array($value)) {
                $collectionType = self::getCollectionType($docComment, $name, $class);
                if ($collectionType !== null && class_exists($collectionType)) {
                    /** @var class-string<object> $collectionType */
                    $hydratedList = [];
                    foreach ($value as $item) {
                        $hydratedList[] = self::hydrate($collectionType, $item);
                    }
                    $arguments[] = $hydratedList;
                    continue;
                }
            }

            // Otherwise, set value directly
            $arguments[] = $value;
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Serialize an object into a snake_case key array.
     *
     * @param object $object
     * @return array<string, mixed>
     */
    public static function serialize(object $object): array
    {
        if ($object instanceof Arrayable) {
            return $object->toArray();
        }

        /** @var class-string<object> $class */
        $class = get_class($object);
        $reflection = new ReflectionClass($class);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            $snakeName = self::camelToSnake($name);

            // Ensure the property is initialized
            if (!$property->isInitialized($object)) {
                continue;
            }

            $value = $property->getValue($object);
            $data[$snakeName] = self::serializeValue($value);
        }

        return $data;
    }

    /**
     * Compare two DTOs for structural equality.
     *
     * @param object $a
     * @param object $b
     * @return bool
     */
    public static function compare(object $a, object $b): bool
    {
        return json_encode(self::serialize($a)) === json_encode(self::serialize($b));
    }

    private static function serializeValue(mixed $value): mixed
    {
        if (is_object($value)) {
            return self::serialize($value);
        }

        if (is_array($value)) {
            $serialized = [];
            foreach ($value as $key => $val) {
                $serialized[$key] = self::serializeValue($val);
            }
            return $serialized;
        }

        return $value;
    }

    /**
     * @param class-string<object> $declaringClass
     */
    private static function getCollectionType(string $docComment, string $paramName, string $declaringClass): ?string
    {
        $pattern = '/@param\s+([^\s]+)\s+\$' . preg_quote($paramName, '/') . '/';
        if (preg_match($pattern, $docComment, $matches)) {
            $type = $matches[1];
            // list<Type> or array<any, Type>
            if (preg_match('/^(?:list|array)<(?:[^,]+,\s*)?([^>]+)>/', $type, $subMatches)) {
                $typeName = trim($subMatches[1], '\\ ');
                return self::resolveClassName($typeName, $declaringClass);
            }
            // Type[]
            if (preg_match('/^([^\s\[\]]+)\[\]$/', $type, $subMatches)) {
                $typeName = trim($subMatches[1], '\\ ');
                return self::resolveClassName($typeName, $declaringClass);
            }
        }
        return null;
    }

    /**
     * @param class-string<object> $declaringClass
     */
    private static function resolveClassName(string $typeName, string $declaringClass): string
    {
        if (class_exists($typeName)) {
            return $typeName;
        }
        // Try namespace of declaring class
        $reflection = new ReflectionClass($declaringClass);
        $namespace = $reflection->getNamespaceName();
        if ($namespace !== '') {
            $namespaced = $namespace . '\\' . $typeName;
            if (class_exists($namespaced)) {
                return $namespaced;
            }
        }
        // Try prefixing with root namespace
        $rootNamespaced = 'Elqora\\Dgp\\' . $typeName;
        if (class_exists($rootNamespaced)) {
            return $rootNamespaced;
        }
        return $typeName;
    }

    private static function camelToSnake(string $string): string
    {
        return strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
