<?php

namespace Elqora\Dgp\Insights;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class Leaderboard implements Arrayable, JsonSerializable
{
    /**
     * @param list<LeaderboardEntry> $entries
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public array $entries,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'entries' => array_map(fn (LeaderboardEntry $entry) => $entry->toArray(), $this->entries),
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
