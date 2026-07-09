<?php

namespace Elqora\Dgp\Insights;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Support\StableIdentifier;
use JsonSerializable;

final readonly class ScoreboardItem implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $key,
        public mixed $value,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $unit = null,
        public array $meta = [],
    ) {
        StableIdentifier::assert($this->key, 'Scoreboard item key');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'title' => $this->title,
            'description' => $this->description,
            'unit' => $this->unit,
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
