<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Snapshots\OrderSnapshot;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class InitializeRequest implements Arrayable, JsonSerializable
{
    public function __construct(
        public string|int $orderId,
        public OrderSnapshot $snapshot,
        public ?RuntimeContext $runtimeContext = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'snapshot' => $this->snapshot->toArray(),
            'runtimeContext' => $this->runtimeContext?->toArray(),
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
