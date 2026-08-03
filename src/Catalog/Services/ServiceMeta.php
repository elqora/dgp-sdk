<?php

namespace Elqora\Dgp\Catalog\Services;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ServiceMeta implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $derived
     */
    public function __construct(
        public array $raw = [],
        public array $derived = [],
    ) {}

    /** @param array<string, mixed>|null $meta */
    public static function from(?array $meta): self
    {
        return new self(derived: $meta ?? []);
    }

    public function get(string $key, string $from = 'derived', mixed $default = null): mixed
    {
        $source = $from === 'raw' ? $this->raw : $this->derived;

        return $source[$key] ?? $default;
    }

    public function has(string $key, string $from = 'derived'): bool
    {
        $source = $from === 'raw' ? $this->raw : $this->derived;

        return array_key_exists($key, $source);
    }

    public function getAny(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->derived)) {
            return $this->derived[$key];
        }

        if (array_key_exists($key, $this->raw)) {
            return $this->raw[$key];
        }

        return $default;
    }

    public function withDerived(string $key, mixed $value): self
    {
        $derived = $this->derived;
        $derived[$key] = $value;

        return new self($this->raw, $derived);
    }

    public function withRaw(string $key, mixed $value): self
    {
        $raw = $this->raw;
        $raw[$key] = $value;

        return new self($raw, $this->derived);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDerivedArray(): array
    {
        return $this->derived;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRawArray(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_replace($this->raw, $this->derived);
    }

    /**
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) $this->toArray();
    }
}
