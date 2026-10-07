<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

/**
 * Read-only view over the readable parts of a product's service schema props.
 */
final readonly class ProductSchemaDoc implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $props
     */
    public function __construct(private array $props = []) {}

    /**
     * @param array<string, mixed> $props
     */
    public static function from(array $props): self
    {
        return new self($props);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->props) ? $this->props[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->props);
    }

    /** @return list<array<string, mixed>> */
    public function filters(): array
    {
        return self::objectList($this->props['filters'] ?? null);
    }

    /** @return array<string, mixed>|null */
    public function filter(string $id): ?array
    {
        foreach ($this->filters() as $filter) {
            if (($filter['id'] ?? null) === $id) {
                return $filter;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function fields(): array
    {
        return self::objectList($this->props['fields'] ?? null);
    }

    /** @return array<string, mixed>|null */
    public function field(string $id): ?array
    {
        foreach ($this->fields() as $field) {
            if (($field['id'] ?? null) === $id) {
                return $field;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function optionsForField(string $fieldId): array
    {
        return self::objectList($this->field($fieldId)['options'] ?? null);
    }

    /** @return list<array<string, mixed>> */
    public function optionsForOption(string $fieldId, string $optionId): array
    {
        return self::objectList($this->option($fieldId, $optionId)['children'] ?? null);
    }

    /** @return array<string, mixed>|null */
    public function option(string $fieldId, string $optionId): ?array
    {
        foreach ($this->optionsForField($fieldId) as $option) {
            $found = self::findOption($option, $optionId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function notices(): array
    {
        return self::objectList($this->props['notices'] ?? null);
    }

    /**
     * Return unique HandlerService IDs referenced by filters, button fields, and options.
     * The order follows the props: filters first, then fields and their recursive options.
     *
     * @return list<string|int>
     */
    public function serviceRefs(): array
    {
        $refs = [];
        $seen = [];

        foreach ($this->filters() as $filter) {
            self::appendServiceRef($filter['service_id'] ?? null, $refs, $seen);
        }

        foreach ($this->fields() as $field) {
            if (($field['button'] ?? false) === true) {
                self::appendServiceRef($field['service_id'] ?? null, $refs, $seen);
            }

            foreach (self::objectList($field['options'] ?? null) as $option) {
                self::appendOptionServiceRefs($option, $refs, $seen);
            }
        }

        return $refs;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->props;
    }

    public function jsonSerialize(): object
    {
        return (object) $this->props;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function objectList(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }

    /** @param array<string, mixed> $option
     *  @return array<string, mixed>|null
     */
    private static function findOption(array $option, string $optionId): ?array
    {
        if (($option['id'] ?? null) === $optionId) {
            return $option;
        }

        foreach (self::objectList($option['children'] ?? null) as $child) {
            $found = self::findOption($child, $optionId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param list<string|int> $refs
     * @param array<string, true> $seen
     */
    private static function appendServiceRef(mixed $value, array &$refs, array &$seen): void
    {
        if (!is_string($value) && !is_int($value)) {
            return;
        }

        $key = is_int($value) ? 'i:' . $value : 's:' . $value;
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $refs[] = $value;
    }

    /**
     * @param array<string, mixed> $option
     * @param list<string|int> $refs
     * @param array<string, true> $seen
     */
    private static function appendOptionServiceRefs(array $option, array &$refs, array &$seen): void
    {
        self::appendServiceRef($option['service_id'] ?? null, $refs, $seen);

        foreach (self::objectList($option['children'] ?? null) as $child) {
            self::appendOptionServiceRefs($child, $refs, $seen);
        }
    }
}
