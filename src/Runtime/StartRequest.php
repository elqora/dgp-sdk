<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Runtime\References\PlanReference;
use JsonSerializable;

final readonly class StartRequest implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public PlanReference $plan,
        public ?RuntimeContext $context = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'plan' => [
                'id' => $this->plan->id,
                'key' => $this->plan->key,
            ],
            'context' => $this->context?->toArray(),
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
