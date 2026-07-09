<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class HandlerService implements Arrayable, JsonSerializable
{
    public ServiceCapabilitySet $capabilities;
    public ServiceMeta $meta;

    /**
     * @param ServiceCapabilitySet|array<string, ServiceCapability|string|array<string, mixed>>|list<ServiceCapability|string|array<string, mixed>> $capabilities
     * @param ServiceMeta|array<string, mixed>|null $meta
     */
    public function __construct(
        public string|int $id,
        public string $name,
        public ?string $description = null,
        public ?string $category = null,
        public ?float $rate = null,
        public int $min = 1,
        public int $max = 1,
        ServiceCapabilitySet|array $capabilities = [],
        ServiceMeta|array|null $meta = null,
    ) {
        $this->capabilities = $capabilities instanceof ServiceCapabilitySet
            ? $capabilities
            : new ServiceCapabilitySet($capabilities);
        $this->meta = $meta instanceof ServiceMeta ? $meta : ServiceMeta::from($meta);
    }

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
            'rate' => $this->rate,
            'min' => $this->min,
            'max' => $this->max,
            'capabilities' => $this->capabilities->toArray(),
            'meta' => $this->meta->toArray(),
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
