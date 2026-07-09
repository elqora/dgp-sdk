<?php

namespace Elqora\Dgp\Insights;

use Elqora\Dgp\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class LeaderboardEntry implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $serviceId,
        public int $rank,
        public int|float|null $score = null,
        public ?string $title = null,
        public array $meta = [],
    ) {
        if (is_string($this->serviceId) && trim($this->serviceId) === '') {
            throw new InvalidArgumentException('Leaderboard service ID must not be empty.');
        }

        if ($this->rank < 1) {
            throw new InvalidArgumentException('Leaderboard rank must be greater than zero.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service_id' => $this->serviceId,
            'rank' => $this->rank,
            'score' => $this->score,
            'title' => $this->title,
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
