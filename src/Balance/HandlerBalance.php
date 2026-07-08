<?php

namespace Elqora\Dgp\Balance;

use Elqora\Dgp\Money\Money;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;
use InvalidArgumentException;

final readonly class HandlerBalance implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public BalanceKind $kind,
        public ?Money $available = null,
        public ?Money $reserved = null,
        public ?Money $total = null,
        public ?string $checkedAt = null,
        public array $meta = [],
    ) {
        if ($kind === BalanceKind::FINITE && $available === null) {
            throw new InvalidArgumentException(
                'Finite balances require an available amount.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'available' => $this->available?->toArray(),
            'reserved' => $this->reserved?->toArray(),
            'total' => $this->total?->toArray(),
            'checked_at' => $this->checkedAt,
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
