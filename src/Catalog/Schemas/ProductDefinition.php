<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ProductDefinition implements Arrayable, JsonSerializable
{
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
        public ?string $schemaVersion = null,
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
        return $this->toArray();
    }
}
