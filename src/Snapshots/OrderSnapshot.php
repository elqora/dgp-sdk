<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshot implements Arrayable, JsonSerializable
{
    /**
     * @param list<string|int> $serviceIds
     * @param array<string, list<string|int>> $serviceIdsByNode
     * @param array<string, mixed>|null $fallbacks
     * @param list<array<string, mixed>> $utilities
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $version,
        public string $mode,
        public string $builtAt,
        public string|int $productId,
        public string $definitionSchemaVersion,
        public OrderSnapshotSelection $selection,
        public OrderSnapshotInputs $inputs,
        public int|float $quantity,
        public OrderSnapshotQuantitySource $quantitySource,
        public int|float $min,
        public int|float $max,
        public array $serviceIds = [],
        public array $serviceIdsByNode = [],
        public ?array $fallbacks = null,
        public array $utilities = [],
        public array $meta = [],
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return self
     */
    public static function fromArray(array $payload): self
    {
        return OrderSnapshotHydrator::hydrate($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'mode' => $this->mode,
            'built_at' => $this->builtAt,
            'product_id' => $this->productId,
            'definition_schema_version' => $this->definitionSchemaVersion,
            'selection' => $this->selection->toArray(),
            'inputs' => $this->inputs->toArray(),
            'quantity' => $this->quantity,
            'quantity_source' => $this->quantitySource->toArray(),
            'min' => $this->min,
            'max' => $this->max,
            'service_ids' => $this->serviceIds,
            'service_ids_by_node' => $this->serviceIdsByNode,
            'fallbacks' => $this->fallbacks,
            'utilities' => $this->utilities,
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

    public function version(): string
    {
        return $this->version;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function builtAt(): string
    {
        return $this->builtAt;
    }

    public function productId(): string|int
    {
        return $this->productId;
    }

    public function definitionSchemaVersion(): string
    {
        return $this->definitionSchemaVersion;
    }

    public function filterId(): string
    {
        return $this->selection->filterId;
    }

    /** @return list<string> */
    public function triggerIds(): array
    {
        return $this->selection->triggerIds;
    }

    /**
     * @return list<OrderSnapshotFieldSelection>
     */
    public function fields(): array
    {
        return $this->selection->fields;
    }

    public function input(string $name, mixed $default = null): mixed
    {
        return $this->inputs->form[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function inputs(): array
    {
        return $this->inputs->form;
    }

    /**
     * @return array<string, list<string>>
     */
    public function selections(): array
    {
        return $this->inputs->selections;
    }

    public function quantity(): int|float
    {
        return $this->quantity;
    }

    public function quantitySource(): OrderSnapshotQuantitySource
    {
        return $this->quantitySource;
    }

    public function min(): int|float
    {
        return $this->min;
    }

    public function max(): int|float
    {
        return $this->max;
    }

    /**
     * @return list<string|int>
     */
    public function serviceIds(): array
    {
        return $this->serviceIds;
    }

    /**
     * @return array<string, list<string|int>>
     */
    public function serviceIdsByNode(): array
    {
        return $this->serviceIdsByNode;
    }

    /**
     * @return list<string|int>
     */
    public function servicesForNode(string $nodeId): array
    {
        return $this->serviceIdsByNode[$nodeId] ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fallbacks(): ?array
    {
        return $this->fallbacks;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function utilities(): array
    {
        return $this->utilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function context(): ?array
    {
        return $this->meta['context'] ?? null;
    }

    /**
     * Resolve fallback service IDs for a primary service, optionally within a node context.
     *
     * @param string|int $serviceId
     * @param string|null $nodeId
     * @return list<string|int>
     */
    public function fallbacksFor(
        string|int $serviceId,
        ?string $nodeId = null,
    ): array {
        $globalList = [];
        $nodeList = [];

        $fallbacks = $this->fallbacks ?? [];

        // 1. Resolve Global Fallbacks
        $globalGroup = $fallbacks['global'] ?? [];
        if (array_key_exists($serviceId, $globalGroup)) {
            $globalList = $globalGroup[$serviceId];
        } elseif (is_numeric($serviceId) && array_key_exists((int) $serviceId, $globalGroup)) {
            $globalList = $globalGroup[(int) $serviceId];
        } elseif (array_key_exists((string) $serviceId, $globalGroup)) {
            $globalList = $globalGroup[(string) $serviceId];
        }

        if (!is_array($globalList)) {
            $globalList = [];
        }

        // 2. Resolve node-specific fallbacks from the canonical node map.
        if ($nodeId !== null) {
            $nodesGroup = $fallbacks['nodes'] ?? [];
            $rawNodeList = $nodesGroup[$nodeId] ?? [];
            $nodeList = is_array($rawNodeList) ? $rawNodeList : [];
        }

        // 3. Combine and Deduplicate preserving order (node-level precedence)
        $combined = array_merge($nodeList, $globalList);

        $seen = [];
        $deduplicated = [];
        foreach ($combined as $item) {
            $normalized = is_numeric($item) ? (int) $item : $item;
            $seenKey = (string) $normalized;
            if (!isset($seen[$seenKey])) {
                $seen[$seenKey] = true;
                $deduplicated[] = $normalized;
            }
        }

        return $deduplicated;
    }
}
