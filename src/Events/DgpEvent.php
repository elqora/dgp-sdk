<?php

namespace Elqora\Dgp\Events;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class DgpEvent implements Arrayable, JsonSerializable
{
    public EventType|string $type;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        EventType|string $type,
        public string $handlerKey,
        public string|int $orderId,
        public ?string $deliveryKey = null,
        public ?string $chargeKey = null,
        public ?string $occurredAt = null,
        public array $payload = [],
        public array $meta = [],
    ) {
        $this->type = $type;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type instanceof EventType ? $this->type->value : $this->type,
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
