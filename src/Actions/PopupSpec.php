<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class PopupSpec implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $variant = 'modal', // modal, drawer, sheet
        public string $size = 'md', // sm, md, lg, xl, full
        public bool $dismissible = true,
        public bool $closeOnBackdrop = true,
        public bool $closeOnEscape = true,
        public string $placement = 'center',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'variant' => $this->variant,
            'size' => $this->size,
            'dismissible' => $this->dismissible,
            'close_on_backdrop' => $this->closeOnBackdrop,
            'close_on_escape' => $this->closeOnEscape,
            'placement' => $this->placement,
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
