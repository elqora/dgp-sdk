<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshotFieldSelection implements Arrayable, JsonSerializable
{
    /**
     * @param list<string> $selectedOptionIds
     */
    public function __construct(
        public string $fieldId,
        public string $fieldType,
        public array $selectedOptionIds = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field_id' => $this->fieldId,
            'field_type' => $this->fieldType,
            'selected_option_ids' => $this->selectedOptionIds,
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
