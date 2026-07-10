<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;
use InvalidArgumentException;

final readonly class ButtonAction implements NextAction
{
    /**
     * @param list<ActionButton> $buttons
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public array $buttons,
        public ?string $label = null,
        public array $meta = [],
    ) {
        $errors = ActionValidator::validateButtons($buttons);

        if ($errors !== []) {
            throw new InvalidArgumentException(reset($errors));
        }
    }

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
            'label' => $this->label,
            'buttons' => array_map(fn (ActionButton $button) => $button->toArray(), $this->buttons),
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
