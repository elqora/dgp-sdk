<?php

namespace Elqora\Dgp\Manifest;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class HandlerManifest implements Arrayable, JsonSerializable
{
    /**
     * @param list<Capability> $capabilities
     * @param list<string> $supportedServiceSchemaVersions
     * @param list<string> $synchronizationModes
     * @param list<string> $supportedNextActionTypes
     * @param array<string, mixed> $limitations
     * @param array<string, mixed> $featureFlags
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $version,
        public array $capabilities = [],
        public array $supportedServiceSchemaVersions = [],
        public array $synchronizationModes = [],
        public bool $webhookSupport = false,
        public array $supportedNextActionTypes = [],
        public array $limitations = [],
        public array $featureFlags = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'capabilities' => array_map(fn (Capability $c) => $c->value, $this->capabilities),
            'supported_service_schema_versions' => $this->supportedServiceSchemaVersions,
            'synchronization_modes' => $this->synchronizationModes,
            'webhook_support' => $this->webhookSupport,
            'supported_next_action_types' => $this->supportedNextActionTypes,
            'limitations' => $this->limitations,
            'feature_flags' => $this->featureFlags,
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
