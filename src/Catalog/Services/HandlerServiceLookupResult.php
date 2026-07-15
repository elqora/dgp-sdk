<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class HandlerServiceLookupResult implements Arrayable, JsonSerializable
{
    /**
     * @param list<HandlerService> $services
     * @param list<string|int> $missingIds
     */
    public function __construct(
        public array $services,
        public array $missingIds = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'services' => array_map(fn (HandlerService $s) => $s->toArray(), $this->services),
            'missing_ids' => $this->missingIds,
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
