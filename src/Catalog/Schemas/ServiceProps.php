<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceProps implements Arrayable, JsonSerializable
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
     */
    public function __construct(
        public array $filters,
        public array $fields,
        public array $orderForTags = [],
        public array $includesForButtons = [],
        public array $excludesForButtons = [],
        public array $optionEffectsForButtons = [],
        public array $valueEffectsForTriggers = [],
        public ?string $schemaVersion = null,
        public ?array $fallbacks = null,
        public ?string $name = null,
        public array $notices = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'filters' => $this->filters,
            'fields' => $this->fields,
            'order_for_tags' => $this->orderForTags,
            'includes_for_buttons' => $this->includesForButtons,
            'excludes_for_buttons' => $this->excludesForButtons,
            'option_effects_for_buttons' => $this->optionEffectsForButtons,
            'value_effects_for_triggers' => $this->valueEffectsForTriggers,
            'schema_version' => $this->schemaVersion,
            'fallbacks' => $this->fallbacks,
            'name' => $this->name,
            'notices' => $this->notices,
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
