<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Actions\Contracts\NextAction;
use JsonSerializable;

final readonly class PreparationResult implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $state
     * @param list<\Elqora\Dgp\Deliveries\InitializationDelivery> $deliveries
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $planId,
        public PreparationStatus $status,
        public array $deliveries = [],
        public ?NextAction $nextAction = null,
        public array $state = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'plan_id' => $this->planId,
            'status' => $this->status->value,
            'deliveries' => array_map(fn ($d) => $d->toArray(), $this->deliveries),
            'next_action' => $this->nextAction?->toArray(),
            'state' => $this->state,
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
