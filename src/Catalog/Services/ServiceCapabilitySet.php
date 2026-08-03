<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class ServiceCapabilitySet implements Arrayable, JsonSerializable
{
    /**
     * @var array<string, ServiceCapability>
     */
    public array $capabilities;

    /**
     * @param array<string, ServiceCapability|string|array<string, mixed>>|list<ServiceCapability|string|array<string, mixed>> $capabilities
     */
    public function __construct(array $capabilities = [], bool $withDefaults = false)
    {
        if ($withDefaults) {
            $capabilities = array_merge($capabilities, self::defaults()->capabilities);
        }

        $normalized = [];
        foreach ($capabilities as $key => $capability) {
            if (is_string($capability)) {
                $capability = new ServiceCapability($capability);
            } elseif (is_array($capability)) {
                $capability = new ServiceCapability(
                    id: (string) ($capability['id'] ?? $key),
                    enabled: (bool) ($capability['enabled'] ?? true),
                    description: isset($capability['description']) ? (string) $capability['description'] : null,
                    meta: is_array($capability['meta'] ?? null) ? $capability['meta'] : [],
                );
            }

            if (!$capability instanceof ServiceCapability) {
                throw new InvalidArgumentException('ServiceCapabilitySet expects service capability values.');
            }

            $normalized[$capability->id] = $capability;
        }

        $this->capabilities = $normalized;
    }

    public static function defaults(
        bool $refill = false,
        bool $cancel = false,
        bool $dripfeed = false,
        bool $contract = false,
    ): self {
        return new self([
            new ServiceCapability('refill', $refill, 'Service supports refill after completion if provider allows.'),
            new ServiceCapability('cancel', $cancel, 'Service supports cancellation if provider allows.'),
            new ServiceCapability('dripfeed', $dripfeed, 'Service supports drip-feed delivery if provider allows.'),
            new ServiceCapability('contract', $contract, 'Service is handled through contract flow or contract rules.'),
        ]);
    }

    public function get(string $id): ?ServiceCapability
    {
        return $this->capabilities[$id] ?? null;
    }

    public function enabled(string $id, bool $default = false): bool
    {
        return $this->capabilities[$id]->enabled ?? $default;
    }

    public function with(ServiceCapability $capability): self
    {
        $next = $this->capabilities;
        $next[$capability->id] = $capability;

        return new self($next);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (ServiceCapability $capability): array => $capability->toArray(),
            $this->capabilities
        );
    }

    /**
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) $this->capabilities;
    }
}
