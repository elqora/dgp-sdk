<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceQuery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public ?string $category = null,
        public ?string $cursor = null,
        public int $limit = 100,
        public array $filters = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'cursor' => $this->cursor,
            'limit' => $this->limit,
            'filters' => $this->filters,
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
