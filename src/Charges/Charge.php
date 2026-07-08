<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Actions\Contracts\NextAction;
use JsonSerializable;
use InvalidArgumentException;

final readonly class Charge implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public ?string $deliveryKey,
        public string $label,
        public Money $amount,
        public ChargeStatus $status,
        public ?Money $paidAmount = null,
        public ?Money $balanceDue = null,
        public ?string $dueAt = null,
        public ?string $paidAt = null,
        public ?NextAction $nextAction = null,
        public array $meta = [],
    ) {
        if (str_starts_with($amount->amount->value, '-')) {
            $allowNegative = $meta['allow_negative'] ?? false;
            if (!$allowNegative) {
                throw new InvalidArgumentException('Charge amount cannot be negative.');
            }
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
            'delivery_key' => $this->deliveryKey,
            'label' => $this->label,
            'amount' => $this->amount->toArray(),
            'status' => $this->status->value,
            'paid_amount' => $this->paidAmount?->toArray(),
            'balance_due' => $this->balanceDue?->toArray(),
            'due_at' => $this->dueAt,
            'paid_at' => $this->paidAt,
            'next_action' => $this->nextAction?->toArray(),
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
