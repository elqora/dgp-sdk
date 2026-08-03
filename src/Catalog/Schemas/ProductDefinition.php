<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ProductDefinition implements Arrayable, JsonSerializable
{
    public const SCHEMA_VERSION = '1';

    /**
     * @param array<int, mixed> $filters
     * @param array<int, mixed> $fields
     * @param array<string, list<string>> $orderForTags
     * @param array<string, list<string>> $includesForButtons
     * @param array<string, list<string>> $excludesForButtons
     * @param array<string, array<string, mixed>> $optionEffectsForButtons
     * @param array<string, array<string, mixed>> $valueEffectsForTriggers
     * @param array<string, mixed>|null $fallbacks
     * @param array<int, mixed> $notices
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $id,
        public string $name,
        public array $filters,
        public array $fields,
        public array $orderForTags = [],
        public array $includesForButtons = [],
        public array $excludesForButtons = [],
        public array $optionEffectsForButtons = [],
        public array $valueEffectsForTriggers = [],
        public string $schemaVersion = self::SCHEMA_VERSION,
        public ?array $fallbacks = null,
        public ?string $description = null,
        public array $notices = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'filters' => $this->filters,
            'fields' => $this->fields,
            'order_for_tags' => $this->orderForTags,
            'includes_for_buttons' => $this->includesForButtons,
            'excludes_for_buttons' => $this->excludesForButtons,
            'option_effects_for_buttons' => $this->optionEffectsForButtons,
            'value_effects_for_triggers' => $this->valueEffectsForTriggers,
            'schema_version' => $this->schemaVersion,
            'fallbacks' => $this->fallbacks,
            'description' => $this->description,
            'notices' => $this->notices,
            'meta' => $this->meta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();
        $data['order_for_tags'] = (object) $this->orderForTags;
        $data['includes_for_buttons'] = (object) $this->includesForButtons;
        $data['excludes_for_buttons'] = (object) $this->excludesForButtons;
        $data['option_effects_for_buttons'] = self::objectMap($this->optionEffectsForButtons, true);
        $data['value_effects_for_triggers'] = self::objectMap($this->valueEffectsForTriggers, true);
        $data['fallbacks'] = self::serializeFallbacks($this->fallbacks);
        $data['filters'] = array_map(self::serializeFilter(...), $this->filters);
        $data['fields'] = array_map(self::serializeField(...), $this->fields);
        $data['notices'] = array_map(self::serializeNotice(...), $this->notices);
        $data['meta'] = (object) $this->meta;

        return $data;
    }

    /**
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    private static function serializeFilter(array $filter): array
    {
        if (array_key_exists('capabilities', $filter) && is_array($filter['capabilities'])) {
            $filter['capabilities'] = (object) $filter['capabilities'];
        }
        if (array_key_exists('meta', $filter) && is_array($filter['meta'])) {
            $filter['meta'] = (object) $filter['meta'];
        }

        return $filter;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private static function serializeField(array $field): array
    {
        foreach (['defaults', 'meta'] as $objectKey) {
            if (array_key_exists($objectKey, $field) && is_array($field[$objectKey])) {
                $field[$objectKey] = (object) $field[$objectKey];
            }
        }
        if (isset($field['options']) && is_array($field['options'])) {
            $field['options'] = array_map(self::serializeOption(...), $field['options']);
        }

        return $field;
    }

    /**
     * @param array<string, mixed> $option
     * @return array<string, mixed>
     */
    private static function serializeOption(array $option): array
    {
        if (array_key_exists('meta', $option) && is_array($option['meta'])) {
            $option['meta'] = (object) $option['meta'];
        }
        if (isset($option['children']) && is_array($option['children'])) {
            $option['children'] = array_map(self::serializeOption(...), $option['children']);
        }

        return $option;
    }

    /**
     * @param array<string, mixed> $notice
     * @return array<string, mixed>
     */
    private static function serializeNotice(array $notice): array
    {
        if (array_key_exists('meta', $notice) && is_array($notice['meta'])) {
            $notice['meta'] = (object) $notice['meta'];
        }

        return $notice;
    }

    /**
     * @param array<string, array<string, mixed>> $map
     */
    private static function objectMap(array $map, bool $nested): object
    {
        if ($nested) {
            foreach ($map as $key => $value) {
                $map[$key] = self::objectMap($value, false);
            }
        } else {
            foreach ($map as $key => $value) {
                $map[$key] = (object) $value;
            }
        }

        return (object) $map;
    }

    /** @param array<string, mixed>|null $fallbacks */
    private static function serializeFallbacks(?array $fallbacks): ?object
    {
        if ($fallbacks === null) {
            return null;
        }
        foreach (['nodes', 'global'] as $key) {
            if (array_key_exists($key, $fallbacks) && is_array($fallbacks[$key])) {
                $fallbacks[$key] = (object) $fallbacks[$key];
            }
        }

        return (object) $fallbacks;
    }
}
