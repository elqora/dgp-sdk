<?php

namespace Elqora\Dgp\Management;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ManagementInstruction implements Arrayable, JsonSerializable
{
    /**
     * @param list<string> $steps
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $steps = [],
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
            'steps' => $this->steps,
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
