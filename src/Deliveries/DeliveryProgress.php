<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Support\Hydrator;
use JsonSerializable;

final readonly class DeliveryProgress implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     * @param list<DeliveryProgressSegment|array<string, mixed>> $segments
     */
    public function __construct(
        public int|float|string|null $current = null,
        public int|float|string|null $target = null,
        public ?float $percent = null,
        public ?string $unit = null,
        public ?string $label = null,
        public ?array $meta = null,
        array $segments = [],
    ) {
        $this->segments = self::normalizeSegments($segments);
    }

    /**
     * @var list<DeliveryProgressSegment>
     */
    public array $segments;

    public static function fromValue(mixed $value): ?self
    {
        return self::fromValueInternal($value);
    }

    public static function fromSegmentValue(mixed $value): ?self
    {
        return self::fromValueInternal($value, false);
    }

    private static function fromValueInternal(mixed $value, bool $withSegments = true): ?self
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof self) {
            if ($withSegments || $value->segments === []) {
                return $value;
            }

            return new self(
                current: $value->current,
                target: $value->target,
                percent: $value->percent,
                unit: $value->unit,
                label: $value->label,
                meta: $value->meta,
            );
        }

        if (is_array($value)) {
            return new self(
                current: $value['current'] ?? null,
                target: $value['target'] ?? null,
                percent: isset($value['percent']) ? (float) $value['percent'] : null,
                unit: $value['unit'] ?? null,
                label: $value['label'] ?? null,
                meta: $value['meta'] ?? null,
                segments: $withSegments && is_array($value['segments'] ?? null) ? $value['segments'] : [],
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
     * @param list<DeliveryProgressSegment|array<string, mixed>> $segments
     * @return list<DeliveryProgressSegment>
     */
    private static function normalizeSegments(array $segments): array
    {
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment instanceof DeliveryProgressSegment) {
                $normalized[] = $segment;
                continue;
            }

            if (is_array($segment)) {
                $normalized[] = new DeliveryProgressSegment(
                    key: (string) ($segment['key'] ?? ''),
                    progress: $segment['progress'] ?? null,
                    label: isset($segment['label']) ? (string) $segment['label'] : null,
                    status: isset($segment['status']) ? (string) $segment['status'] : null,
                    sequence: isset($segment['sequence']) && (is_int($segment['sequence']) || is_float($segment['sequence']))
                        ? $segment['sequence']
                        : null,
                    meta: is_array($segment['meta'] ?? null) ? $segment['meta'] : null,
                    isPublic: isset($segment['is_public']) ? (bool) $segment['is_public'] : true,
                    buttons: self::normalizeButtons($segment['buttons'] ?? []),
                );
            }
        }

        return $normalized;
    }

    /**
     * @return list<ActionButton>
     */
    private static function normalizeButtons(mixed $buttons): array
    {
        if (!is_array($buttons)) {
            return [];
        }

        $normalized = [];
        foreach ($buttons as $button) {
            if ($button instanceof ActionButton) {
                $normalized[] = $button;
                continue;
            }

            if (is_array($button)) {
                $normalized[] = Hydrator::hydrate(ActionButton::class, $button);
            }
        }

        return $normalized;
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
            'segments' => array_map(fn (DeliveryProgressSegment $segment): array => $segment->toArray(), $this->segments),
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
