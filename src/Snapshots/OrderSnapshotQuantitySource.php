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
        public ?string $id = null,
        public ?array $rule = null,
        public ?bool $defaultedFromHost = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'id' => $this->id,
            'rule' => $this->rule,
            'defaultedFromHost' => $this->defaultedFromHost,
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
