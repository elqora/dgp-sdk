<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceSchemaDefinition implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|ServiceProps $props
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $id,
        public string $name,
        public array|ServiceProps $props,
        public ?string $schemaVersion = null,
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
            'name' => $this->name,
            'props' => $this->props instanceof Arrayable ? $this->props->toArray() : $this->props,
            'schema_version' => $this->schemaVersion,
            'description' => $this->description,
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
