<?php

namespace Elqora\Dgp\Assets;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class PrivateAsset implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $mediaType,
        public ?int $size = null,
        public ?string $expiresAt = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'media_type' => $this->mediaType,
            'size' => $this->size,
            'expires_at' => $this->expiresAt,
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
