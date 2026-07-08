<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Actions\Contracts\NextAction;
use JsonSerializable;

final readonly class StartResult implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $state
     * @param list<\Elqora\Dgp\Deliveries\FulfillmentDelivery> $deliveries
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public array $state,
        public array $deliveries = [],
        public ?NextAction $nextAction = null,
        public array $meta = [],
        public string|int|null $planId = null,
        public ?string $planKey = null,
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
            'plan_id' => $this->planId,
            'plan_key' => $this->planKey,
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
