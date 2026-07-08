<?php

declare(strict_types=1);

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

/**
 * Auxiliary information supplied to a runtime operation.
 */
final readonly class RuntimeContext implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public array $context = [],
        public array $meta = [],
    ) {}

    /**
     * @return array{
     *     context: array<string, mixed>,
     *     meta: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
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
