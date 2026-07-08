<?php

namespace Elqora\Dgp\Events;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class DgpEvent implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $handlerKey,
        public string|int $orderId,
        public ?string $deliveryKey = null,
        public ?string $chargeKey = null,
        public ?string $occurredAt = null,
        public array $payload = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'handler_key' => $this->handlerKey,
            'order_id' => $this->orderId,
            'delivery_key' => $this->deliveryKey,
            'charge_key' => $this->chargeKey,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
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
