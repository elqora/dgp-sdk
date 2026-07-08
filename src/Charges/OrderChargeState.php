<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Money\Money;
use JsonSerializable;
use InvalidArgumentException;

final readonly class OrderChargeState implements Arrayable, JsonSerializable
{
    /**
     * @param list<\Elqora\Dgp\Charges\ChargeStatusView> $charges
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public array $charges,
        public Money $total,
        public Money $paid,
        public Money $balanceDue,
        public bool $satisfied,
        public array $meta = [],
    ) {
        $currencyCode = null;
        foreach ($charges as $charge) {
            if (!$charge instanceof ChargeStatusView) {
                continue;
            }
            $code = $charge->amount->currency->code;
            if ($currencyCode === null) {
                $currencyCode = $code;
            } elseif ($currencyCode !== $code) {
                throw new InvalidArgumentException('Multi-currency aggregation is not supported. All charges must share the same currency.');
            }
        }

        if ($currencyCode !== null) {
            if ($total->currency->code !== $currencyCode ||
                $paid->currency->code !== $currencyCode ||
                $balanceDue->currency->code !== $currencyCode
            ) {
                throw new InvalidArgumentException('Totals, paid, and balance due currencies must match the charges currency.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'charges' => array_map(fn ($c) => $c->toArray(), $this->charges),
            'total' => $this->total->toArray(),
            'paid' => $this->paid->toArray(),
            'balance_due' => $this->balanceDue->toArray(),
            'satisfied' => $this->satisfied,
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
