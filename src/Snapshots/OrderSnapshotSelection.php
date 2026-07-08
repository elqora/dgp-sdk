<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshotSelection implements Arrayable, JsonSerializable
{
    /**
     * @param list<string> $buttons
     * @param list<OrderSnapshotFieldSelection> $fields
     */
    public function __construct(
        public string $tag,
        public array $buttons = [],
        public array $fields = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tag' => $this->tag,
            'buttons' => $this->buttons,
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
