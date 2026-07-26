<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionValidator;
use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Interactions\Contracts\Interaction;
use InvalidArgumentException;
use JsonSerializable;

final readonly class Charge implements Arrayable, JsonSerializable
{
    /**
     * @param list<ChargePayment> $payments
     * @param array<string, mixed> $meta
     * @param list<\Elqora\Dgp\Actions\ActionButton> $buttons
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public ?ChargeTarget $target,
        public string $label,
        public Money $amount,
        public ChargeStatus $status,
        public ?Money $paidAmount = null,
        public ?Money $balanceDue = null,
        public array $payments = [],
        public ?string $dueAt = null,
        public ?string $paidAt = null,
        public ?Interaction $nextAction = null,
        public array $meta = [],
        public array $buttons = [],
    ) {
        $errors = ActionValidator::validateButtons($buttons);

        if ($errors !== []) {
            throw new InvalidArgumentException(reset($errors));
        }

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
            'target' => $this->target?->toArray(),
            'label' => $this->label,
            'amount' => $this->amount->toArray(),
            'status' => $this->status->value,
            'paid_amount' => $this->paidAmount?->toArray(),
            'balance_due' => $this->balanceDue?->toArray(),
            'payments' => array_map(fn (ChargePayment $payment) => $payment->toArray(), $this->payments),
            'due_at' => $this->dueAt,
            'paid_at' => $this->paidAt,
            'buttons' => array_map(fn (ActionButton $button): array => $button->toArray(), $this->buttons),
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
