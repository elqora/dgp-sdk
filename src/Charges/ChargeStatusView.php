<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Money\Money;
use JsonSerializable;

final readonly class ChargeStatusView implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $id,
        public string $key,
        public ChargeStatus $status,
        public Money $amount,
        public Money $paid,
        public Money $balanceDue,
        public bool $satisfied,
        public ?string $paidAt = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'status' => $this->status->value,
            'amount' => $this->amount->toArray(),
            'paid' => $this->paid->toArray(),
            'balance_due' => $this->balanceDue->toArray(),
            'satisfied' => $this->satisfied,
            'paid_at' => $this->paidAt,
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
