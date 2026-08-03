<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceCapability implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public bool $enabled = true,
        public ?string $description = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'description' => $this->description,
            'meta' => $this->meta,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'enabled' => $this->enabled,
            'description' => $this->description,
            'meta' => (object) $this->meta,
        ];
    }
}
