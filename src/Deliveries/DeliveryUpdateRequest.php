<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class DeliveryUpdateRequest implements Arrayable, JsonSerializable
{
    /**
     * @param list<\Elqora\Dgp\Deliveries\Delivery> $deliveries
     * @param array<string, mixed> $context
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public array $deliveries,
        public array $context = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'deliveries' => array_map(fn ($d) => $d->toArray(), $this->deliveries),
            'context' => $this->context,
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
