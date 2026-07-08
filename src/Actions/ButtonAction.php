<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class ButtonAction implements NextAction
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $value,
        public string $kind,
        public ?string $label = null,
        public ?string $icon = null,
        public string $style = 'default',
        public ?string $tooltip = null,
        public bool $disabled = false,
        public ?string $disabledReason = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'button';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'value' => $this->value,
            'kind' => $this->kind,
            'label' => $this->label,
            'icon' => $this->icon,
            'style' => $this->style,
            'tooltip' => $this->tooltip,
            'disabled' => $this->disabled,
            'disabled_reason' => $this->disabledReason,
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
