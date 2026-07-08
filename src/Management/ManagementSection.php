<?php

namespace Elqora\Dgp\Management;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ManagementSection implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $type = 'default', // default, details, debug, sidebar
        public ?string $description = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
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
