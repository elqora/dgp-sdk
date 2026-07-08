<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshot implements Arrayable, JsonSerializable
{
    /**
     * @param list<string|int> $services
     * @param array<string, list<string|int>> $serviceMap
     * @param array<string, mixed>|null $fallbacks
     * @param list<array<string, mixed>> $utilities
     * @param array<string, mixed>|null $warnings
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public string $version,
        public string $mode,
        public string $builtAt,
        public OrderSnapshotSelection $selection,
        public OrderSnapshotInputs $inputs,
        public int|float $quantity,
        public OrderSnapshotQuantitySource $quantitySource,
        public int|float $min,
        public int|float $max,
        public array $services = [],
        public array $serviceMap = [],
        public ?array $fallbacks = null,
        public array $utilities = [],
        public ?array $warnings = null,
        public ?array $meta = null,
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
            'builtAt' => $this->builtAt,
            'selection' => $this->selection->toArray(),
            'inputs' => $this->inputs->toArray(),
            'quantity' => $this->quantity,
            'quantitySource' => $this->quantitySource->toArray(),
            'min' => $this->min,
            'max' => $this->max,
            'services' => $this->services,
            'serviceMap' => $this->serviceMap,
            'fallbacks' => $this->fallbacks,
            'utilities' => $this->utilities,
            'warnings' => $this->warnings,
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

    public function tag(): string
    {
        return $this->selection->tag;
    }

    /**
     * @return list<string>
     */
    public function buttons(): array
    {
        return $this->selection->buttons;
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
    public function services(): array
    {
        return $this->services;
    }

    /**
     * @return array<string, list<string|int>>
     */
    public function serviceMap(): array
    {
        return $this->serviceMap;
    }

    /**
     * @return list<string|int>
     */
    public function servicesForNode(string $nodeId): array
    {
        return $this->serviceMap[$nodeId] ?? [];
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
     * @return array<string, mixed>|null
     */
    public function warnings(): ?array
    {
        return $this->warnings;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function meta(): ?array
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

        // 2. Resolve Node Fallbacks
        if ($nodeId !== null) {
            $nodesGroup = $fallbacks['nodes'] ?? [];
            $serviceNodeGroup = null;

            if (array_key_exists($serviceId, $nodesGroup)) {
                $serviceNodeGroup = $nodesGroup[$serviceId];
            } elseif (is_numeric($serviceId) && array_key_exists((int) $serviceId, $nodesGroup)) {
                $serviceNodeGroup = $nodesGroup[(int) $serviceId];
            } elseif (array_key_exists((string) $serviceId, $nodesGroup)) {
                $serviceNodeGroup = $nodesGroup[(string) $serviceId];
            }

            if (is_array($serviceNodeGroup)) {
                $rawNodeList = null;
                if (array_key_exists($nodeId, $serviceNodeGroup)) {
                    $rawNodeList = $serviceNodeGroup[$nodeId];
                }

                if (is_array($rawNodeList)) {
                    $nodeList = $rawNodeList;
                }
            }
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
