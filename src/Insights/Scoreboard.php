<?php

namespace Elqora\Dgp\Insights;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Support\StableIdentifier;
use JsonSerializable;

final readonly class Scoreboard implements Arrayable, JsonSerializable
{
    /**
     * @param list<ScoreboardItem> $items
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public array $items,
        public array $meta = [],
    ) {
        StableIdentifier::assertUnique(
            array_map(fn (ScoreboardItem $item) => $item->key, $this->items),
            'Scoreboard item key'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(fn (ScoreboardItem $item) => $item->toArray(), $this->items),
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
