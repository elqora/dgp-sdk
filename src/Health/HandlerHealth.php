<?php

namespace Elqora\Dgp\Health;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class HandlerHealth implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public HealthStatus $status,
        public ?string $message = null,
        public ?string $checkedAt = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->message,
            'checked_at' => $this->checkedAt,
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
