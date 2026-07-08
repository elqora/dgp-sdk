<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class StartRequest implements Arrayable, JsonSerializable
{
    public function __construct(
        public string|int $planId,
        public ?RuntimeContext $runtimeContext = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'planId' => $this->planId,
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
