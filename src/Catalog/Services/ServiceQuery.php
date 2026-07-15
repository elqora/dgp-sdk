<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceQuery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $filters
     * @param list<HandlerServiceState>|null $states
     */
    public function __construct(
        public ?string $category = null,
        public ?string $cursor = null,
        public int $limit = 100,
        public array $filters = [],
        public ?array $states = null,
        public bool $includeUnavailable = false,
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
            'states' => $this->states !== null
                ? array_map(fn (HandlerServiceState $state) => $state->value, $this->states)
                : null,
            'include_unavailable' => $this->includeUnavailable,
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
