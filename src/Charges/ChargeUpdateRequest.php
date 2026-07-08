<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ChargeUpdateRequest implements Arrayable, JsonSerializable
{
    /**
     * @param list<\Elqora\Dgp\Charges\Charge> $charges
     * @param array<string, mixed> $context
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public array $charges,
        public array $context = [],
        public array $meta = [],
        public ?string $updateId = null,
        public ?string $operationKey = null,
        public ?string $occurredAt = null,
        public ?string $source = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'charges' => array_map(fn ($c) => $c->toArray(), $this->charges),
            'context' => $this->context,
            'meta' => $this->meta,
            'update_id' => $this->updateId,
            'operation_key' => $this->operationKey,
            'occurred_at' => $this->occurredAt,
            'source' => $this->source,
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
