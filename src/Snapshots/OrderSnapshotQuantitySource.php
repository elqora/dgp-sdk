<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshotQuantitySource implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $rule
     */
    public function __construct(
        public string $kind,
        public ?string $nodeId,
        public ?array $rule = null,
        public bool $defaultedFromHost = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'node_id' => $this->nodeId,
            'rule' => $this->rule,
            'defaulted_from_host' => $this->defaultedFromHost,
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
