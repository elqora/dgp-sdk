<?php

namespace Elqora\Dgp\Charges;

use Elqora\Dgp\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class ChargeTarget implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public ChargeTargetType|string $type,
        public string|int|null $id = null,
        public ?string $key = null,
        public ?ChargeTarget $parent = null,
        public array $meta = [],
    ) {
        if ($id === null && $key === null) {
            throw new InvalidArgumentException('A charge target ID or key is required.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type instanceof ChargeTargetType ? $this->type->value : $this->type,
            'id' => $this->id,
            'key' => $this->key,
            'parent' => $this->parent?->toArray(),
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
