<?php

namespace Elqora\Dgp\Catalog\Schemas;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceSchemaQuery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public ?string $schemaVersion = null,
        public ?string $cursor = null,
        public int $limit = 100,
        public array $filters = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'cursor' => $this->cursor,
            'limit' => $this->limit,
            'filters' => $this->filters,
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
