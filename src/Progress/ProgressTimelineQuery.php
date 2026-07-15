<?php

namespace Elqora\Dgp\Progress;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ProgressTimelineQuery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?ProgressSource $source = null,
        public ?int $limit = null,
        public ?string $cursor = null,
        public bool $ascending = true,
        public ?array $meta = null,
        public ?string $segmentKey = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'source' => $this->source?->value,
            'limit' => $this->limit,
            'cursor' => $this->cursor,
            'ascending' => $this->ascending,
            'meta' => $this->meta,
            'segment_key' => $this->segmentKey,
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
