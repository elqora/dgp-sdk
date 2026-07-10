<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ChargePayment implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public string $key,
        public Money $amount,
        public ChargePaymentStatus $status,
        public ?string $paidAt = null,
        public ?string $method = null,
        public ?string $reference = null,
        public ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'amount' => $this->amount->toArray(),
            'status' => $this->status->value,
            'paid_at' => $this->paidAt,
            'method' => $this->method,
            'reference' => $this->reference,
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
