<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;
use Elqora\Dgp\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class ActionButton implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public string $value,
        public ActionButtonKind $kind = ActionButtonKind::TEXT,
        public ?string $label = null,
        public ?string $icon = null,
        public ActionButtonStyle $style = ActionButtonStyle::DEFAULT,
        public ?string $tooltip = null,
        public ?bool $disabled = null,
        public ?string $disabledReason = null,
        public ?array $meta = null,
        public ?NextAction $nextAction = null,
    ) {
        $errors = ActionValidator::validateButton($this);

        if ($errors !== []) {
            throw new InvalidArgumentException(reset($errors));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'kind' => $this->kind->value,
            'label' => $this->label,
            'icon' => $this->icon,
            'style' => $this->style->value,
            'tooltip' => $this->tooltip,
            'disabled' => $this->disabled,
            'disabled_reason' => $this->disabledReason,
            'meta' => $this->meta,
            'next_action' => $this->nextAction?->toArray(),
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
