<?php

namespace Elqora\Dgp\Audits;

use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class AuditQuery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public ?AuditLevel $level = null,
        public ?string $category = null,
        public ?string $code = null,
        public string|int|null $orderId = null,
        public ?DeliveryReference $delivery = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?int $limit = null,
        public ?string $cursor = null,
        public bool $ascending = false,
        public ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level?->value,
            'category' => $this->category,
            'code' => $this->code,
            'order_id' => $this->orderId,
            'delivery' => $this->delivery?->toArray(),
            'from' => $this->from,
            'to' => $this->to,
            'limit' => $this->limit,
            'cursor' => $this->cursor,
            'ascending' => $this->ascending,
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
