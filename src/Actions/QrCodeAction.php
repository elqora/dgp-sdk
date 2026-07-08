<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class QrCodeAction implements NextAction
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $data,
        public ?string $label = null,
        public ?string $description = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'qr_code';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'data' => $this->data,
            'label' => $this->label,
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
