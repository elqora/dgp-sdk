<?php

namespace Elqora\Dgp\Runtime\References;

use Elqora\Dgp\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class DeliveryReference implements Arrayable, JsonSerializable
{
    public function __construct(
        public string|int|null $id = null,
        public ?string $key = null,
    ) {
        if ($id === null && $key === null) {
            throw new InvalidArgumentException(
                'A delivery ID or key is required.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
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
