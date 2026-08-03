<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshotSelection implements Arrayable, JsonSerializable
{
    /**
     * @param list<string> $triggerIds
     * @param list<OrderSnapshotFieldSelection> $fields
     */
    public function __construct(
        public string $filterId,
        public array $triggerIds = [],
        public array $fields = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'filter_id' => $this->filterId,
            'trigger_ids' => $this->triggerIds,
            'fields' => array_map(fn ($f) => $f->toArray(), $this->fields),
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
