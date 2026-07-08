<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class HandlerService implements Arrayable, JsonSerializable
{
    /**
     * @param list<string> $capabilities
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $id,
        public string $name,
        public ?string $description = null,
        public ?string $category = null,
        public array $capabilities = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'capabilities' => $this->capabilities,
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
