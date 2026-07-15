<?php

namespace Elqora\Dgp\Audits;

use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Support\StableIdentifier;
use InvalidArgumentException;
use JsonSerializable;

final readonly class AuditRecord implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $context
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public AuditLevel $level,
        public string $message,
        public string $occurredAt,
        public string|int|null $orderId = null,
        public ?DeliveryReference $delivery = null,
        public ?string $category = null,
        public ?string $code = null,
        public ?array $context = null,
        public ?array $meta = null,
    ) {
        StableIdentifier::assert($this->key, 'Audit record key');

        if (trim($this->message) === '') {
            throw new InvalidArgumentException('Audit record message must not be empty.');
        }

        if (trim($this->occurredAt) === '') {
            throw new InvalidArgumentException('Audit record occurredAt must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'level' => $this->level->value,
            'message' => $this->message,
            'occurred_at' => $this->occurredAt,
            'order_id' => $this->orderId,
            'delivery' => $this->delivery?->toArray(),
            'category' => $this->category,
            'code' => $this->code,
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
