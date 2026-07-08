<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class PopoverSpec implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $size = 'md',
        public string $placement = 'bottom',
        public string $alignment = 'center',
        public int $offset = 8,
        public bool $collisionHandler = true,
        public bool $dismissible = true,
        public bool $closeOnOutsideInteraction = true,
        public bool $closeOnEscape = true,
        public bool $modal = false,
        public ?string $widthConstraints = null,
        public ?string $heightConstraints = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'size' => $this->size,
            'placement' => $this->placement,
            'alignment' => $this->alignment,
            'offset' => $this->offset,
            'collision_handler' => $this->collisionHandler,
            'dismissible' => $this->dismissible,
            'close_on_outside_interaction' => $this->closeOnOutsideInteraction,
            'close_on_escape' => $this->closeOnEscape,
            'modal' => $this->modal,
            'width_constraints' => $this->widthConstraints,
            'height_constraints' => $this->heightConstraints,
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
