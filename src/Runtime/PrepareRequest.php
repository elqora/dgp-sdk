<?php

namespace Elqora\Dgp\Runtime;

use InvalidArgumentException;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class PrepareRequest implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public Plan $plan,
        public ?RuntimeContext $context = null,
        public array $meta = [],
    ) {
        if ($this->plan->id === null) {
            throw new InvalidArgumentException(
                'Preparation requires a persisted plan ID.'
            );
        }

        foreach ($this->plan->deliveries as $delivery) {
            if ($delivery->id === null) {
                throw new InvalidArgumentException(
                    'Preparation requires persisted delivery IDs.'
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'plan' => $this->plan->toArray(),
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
