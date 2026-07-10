<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ActionTarget implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public ActionTargetType|string $type,
        public string|int $id,
        public ?string $key = null,
        public ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type instanceof ActionTargetType ? $this->type->value : $this->type,
            'id' => $this->id,
            'key' => $this->key,
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
