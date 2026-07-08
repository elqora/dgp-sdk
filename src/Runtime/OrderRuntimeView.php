<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderRuntimeView implements Arrayable, JsonSerializable
{
    /**
     * @param list<Plan> $plans
     * @param list<StartResult> $startResults
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public array $plans,
        public array $startResults,
        public ?Plan $currentPlan = null,
        public ?StartResult $currentStartResult = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'plans' => array_map(fn ($p) => $p->toArray(), $this->plans),
            'start_results' => array_map(fn ($s) => $s->toArray(), $this->startResults),
            'current_plan' => $this->currentPlan?->toArray(),
            'current_start_result' => $this->currentStartResult?->toArray(),
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
