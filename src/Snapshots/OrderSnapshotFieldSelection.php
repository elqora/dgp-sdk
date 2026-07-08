<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshotFieldSelection implements Arrayable, JsonSerializable
{
    /**
     * @param list<string>|null $selectedOptions
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?array $selectedOptions = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'selectedOptions' => $this->selectedOptions,
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
