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

    /**
     * @param array<string, mixed>|null $meta
     */
    public static function from(?array $meta): self
    {
        if ($meta === null) {
            return new self();
        }

        $keys = array_keys($meta);
        $hasRaw = array_key_exists('raw', $meta);
        $hasDerived = array_key_exists('derived', $meta);

        if ($hasRaw || $hasDerived) {
            $otherKeys = array_diff($keys, ['raw', 'derived']);
            $rawIsArray = !$hasRaw || is_array($meta['raw']);
            $derivedIsArray = !$hasDerived || is_array($meta['derived']);

            if ($otherKeys === [] && $rawIsArray && $derivedIsArray) {
                return new self(
                    raw: (array) ($meta['raw'] ?? []),
                    derived: (array) ($meta['derived'] ?? []),
                );
            }
        }

        return new self(derived: $meta);
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
        return [
            'raw' => $this->raw,
            'derived' => $this->derived,
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
