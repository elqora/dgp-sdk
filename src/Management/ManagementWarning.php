<?php

namespace Elqora\Dgp\Management;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ManagementWarning implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $message,
        public string $severity = 'warning', // info, warning, error
        public ?string $title = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'severity' => $this->severity,
            'title' => $this->title,
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
