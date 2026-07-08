<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Actions\Contracts\NextAction;
use JsonSerializable;

final readonly class Plan implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $state
     * @param list<\Elqora\Dgp\Deliveries\InitializationDelivery> $deliveries
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public array $state,
        public array $deliveries = [],
        public ?NextAction $nextAction = null,
        public array $meta = [],
        public int $revision = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'state' => $this->state,
            'deliveries' => array_map(fn ($d) => $d->toArray(), $this->deliveries),
            'next_action' => $this->nextAction?->toArray(),
            'meta' => $this->meta,
            'revision' => $this->revision,
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
