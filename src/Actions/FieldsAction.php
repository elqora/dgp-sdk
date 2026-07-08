<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class FieldsAction implements NextAction
{
    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public array $fields,
        public string $submitLabel = 'Submit',
        public ?string $cancelLabel = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'fields';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'fields' => $this->fields,
            'submit_label' => $this->submitLabel,
            'cancel_label' => $this->cancelLabel,
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
