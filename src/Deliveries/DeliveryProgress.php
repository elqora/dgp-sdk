<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class DeliveryProgress implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public int|float|string|null $current = null,
        public int|float|string|null $target = null,
        public ?float $percent = null,
        public ?string $unit = null,
        public ?string $label = null,
        public ?array $meta = null,
    ) {}

    public static function fromValue(mixed $value): ?self
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            return new self(
                current: $value['current'] ?? null,
                target: $value['target'] ?? null,
                percent: isset($value['percent']) ? (float) $value['percent'] : null,
                unit: $value['unit'] ?? null,
                label: $value['label'] ?? null,
                meta: $value['meta'] ?? null,
            );
        }

        if (is_int($value) || is_float($value)) {
            return new self(current: $value, percent: (float) $value);
        }

        if (is_string($value)) {
            return new self(current: $value, label: $value);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'current' => $this->current,
            'target' => $this->target,
            'percent' => $this->percent,
            'unit' => $this->unit,
            'label' => $this->label,
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
