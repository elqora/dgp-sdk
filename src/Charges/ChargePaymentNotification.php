<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ChargePaymentNotification implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public string $chargeKey,
        public string $paymentKey,
        public Money $amount,
        public ChargePaymentStatus $status,
        public string $occurredAt,
        public string|int|null $chargeId = null,
        public string|int|null $paymentId = null,
        public ?string $paidAt = null,
        public ?ChargeStatus $resultingChargeStatus = null,
        public ?ChargeTarget $chargeTarget = null,
        public array $context = [],
        public array $meta = [],
        public ?string $notificationId = null,
        public ?string $source = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'charge_key' => $this->chargeKey,
            'payment_key' => $this->paymentKey,
            'amount' => $this->amount->toArray(),
            'status' => $this->status->value,
            'occurred_at' => $this->occurredAt,
            'charge_id' => $this->chargeId,
            'payment_id' => $this->paymentId,
            'paid_at' => $this->paidAt,
            'resulting_charge_status' => $this->resultingChargeStatus?->value,
            'charge_target' => $this->chargeTarget?->toArray(),
            'context' => $this->context,
            'meta' => $this->meta,
            'notification_id' => $this->notificationId,
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
