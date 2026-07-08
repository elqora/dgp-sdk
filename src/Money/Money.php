<?php

namespace Elqora\Dgp\Money;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class Money implements Arrayable, JsonSerializable
{
    public function __construct(
        public Amount $amount,
        public Currency $currency,
    ) {}

    /**
     * @return array{amount: string, currency: string}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount->value,
            'currency' => $this->currency->code,
        ];
    }

    /**
     * @return array{amount: string, currency: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
