<?php

namespace Elqora\Dgp\Support;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
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
        if ($class === \Elqora\Dgp\Catalog\Services\ServiceCapabilitySet::class) {
            if (!is_array($data)) {
                throw new InvalidArgumentException('Service capability set data must be an array.');
            }

            $capabilities = [];
            foreach ($data as $capability) {
                $capabilities[] = self::hydrate(\Elqora\Dgp\Catalog\Services\ServiceCapability::class, $capability);
            }

            /** @var T $capabilitySet */
            $capabilitySet = new \Elqora\Dgp\Catalog\Services\ServiceCapabilitySet($capabilities);
            return $capabilitySet;
        }

        if ($class === \Elqora\Chart\Charts\Chart::class) {
            if (!is_array($data)) {
                throw new InvalidArgumentException('Chart data must be an array.');
            }

            /** @var T $chart */
            $chart = \Elqora\Chart\Charts\Chart::fromArray($data);
            return $chart;
        }

        if ($class === \Elqora\Dgp\Actions\Contracts\NextAction::class) {
            if (is_array($data) && isset($data['type'])) {
                $concreteClass = self::resolveConcreteActionClass((string)$data['type']);
                if ($concreteClass !== null) {
                    /** @var class-string<T> $concreteClass */
                    /** @var T $hydratedAction */
                    $hydratedAction = self::hydrate($concreteClass, $data);
                    return $hydratedAction;
                }

                throw new InvalidArgumentException("Unsupported next action type '{$data['type']}'.");
            }
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new InvalidArgumentException("Class or interface {$class} does not exist.");
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

        $data = self::liftLegacyButtonNextAction($class, $data);

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
            if ($type instanceof ReflectionUnionType && $class === \Elqora\Dgp\Charges\ChargeTarget::class && $name === 'type') {
                if ($value === null && $parameter->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }

                $hydratedUnionValue = self::hydrateUnionValue($type, $value);
                if ($hydratedUnionValue !== null) {
                    $arguments[] = $hydratedUnionValue;
                    continue;
                }
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                /** @var class-string<object> $typeName */
                $typeName = $type->getName();
                if ($value === null && $parameter->allowsNull()) {
                    $arguments[] = null;
                } elseif (is_subclass_of($typeName, \BackedEnum::class)) {
                    $arguments[] = $typeName::from($value);
                } else {
                    $arguments[] = self::hydrate($typeName, $value);
                }
                continue;
            }

            // Check if collection parameter
            if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && is_array($value)) {
                $collectionType = self::getCollectionType($docComment, $name, $class);
                if ($collectionType !== null && (class_exists($collectionType) || enum_exists($collectionType))) {
                    /** @var class-string<object> $collectionType */
                    $hydratedList = [];
                    foreach ($value as $item) {
                        if (is_subclass_of($collectionType, \BackedEnum::class)) {
                            $hydratedList[] = $collectionType::from($item);
                        } else {
                            $hydratedList[] = self::hydrate($collectionType, $item);
                        }
                    }
                    $arguments[] = $hydratedList;
                    continue;
                }
            }

            // Otherwise, set value directly
            $arguments[] = $value;
        }

        /** @var T $instance */
        $instance = $reflection->newInstanceArgs($arguments);
        return $instance;
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

    private static function hydrateUnionValue(ReflectionUnionType $type, mixed $value): mixed
    {
        foreach ($type->getTypes() as $unionType) {
            if (!$unionType instanceof ReflectionNamedType || $unionType->isBuiltin()) {
                continue;
            }

            /** @var class-string<object> $typeName */
            $typeName = $unionType->getName();
            if (is_subclass_of($typeName, \BackedEnum::class)) {
                try {
                    return $typeName::from($value);
                } catch (\ValueError) {
                    continue;
                }
            }
        }

        return null;
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
        if (enum_exists($typeName)) {
            return $typeName;
        }
        // Try namespace of declaring class
        $reflection = new ReflectionClass($declaringClass);
        $namespace = $reflection->getNamespaceName();
        if ($namespace !== '') {
            $namespaced = $namespace . '\\' . $typeName;
            if (class_exists($namespaced) || enum_exists($namespaced)) {
                return $namespaced;
            }
        }
        // Try prefixing with root namespace
        $rootNamespaced = 'Elqora\\Dgp\\' . $typeName;
        if (class_exists($rootNamespaced) || enum_exists($rootNamespaced)) {
            return $rootNamespaced;
        }
        return $typeName;
    }

    private static function camelToSnake(string $string): string
    {
        return strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    private static function resolveConcreteActionClass(string $type): ?string
    {
        $map = [
            'redirect' => \Elqora\Dgp\Actions\RedirectAction::class,
            'custom' => \Elqora\Dgp\Actions\CustomAction::class,
            'inline' => \Elqora\Dgp\Actions\InlineAction::class,
            'instructions' => \Elqora\Dgp\Actions\InstructionsAction::class,
            'popover' => \Elqora\Dgp\Actions\PopoverAction::class,
            'popup' => \Elqora\Dgp\Actions\PopupAction::class,
            'qr_code' => \Elqora\Dgp\Actions\QrCodeAction::class,
        ];
        return $map[$type] ?? null;
    }

    /**
     * @param class-string<object> $class
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function liftLegacyButtonNextAction(string $class, array $data): array
    {
        $classesWithButtons = [
            \Elqora\Dgp\Runtime\Plan::class => true,
            \Elqora\Dgp\Runtime\PreparationResult::class => true,
            \Elqora\Dgp\Runtime\StartResult::class => true,
            \Elqora\Dgp\Deliveries\InitializationDelivery::class => true,
            \Elqora\Dgp\Deliveries\FulfillmentDelivery::class => true,
            \Elqora\Dgp\Charges\Charge::class => true,
        ];

        if (!isset($classesWithButtons[$class])) {
            return $data;
        }

        $nextAction = $data['next_action'] ?? $data['nextAction'] ?? null;
        if (!is_array($nextAction) || ($nextAction['type'] ?? null) !== 'button') {
            return $data;
        }

        if (!array_key_exists('buttons', $data) && isset($nextAction['buttons']) && is_array($nextAction['buttons'])) {
            $data['buttons'] = $nextAction['buttons'];
        }

        unset($data['next_action'], $data['nextAction']);

        return $data;
    }
}
