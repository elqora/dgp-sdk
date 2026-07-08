<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class CustomAction implements NextAction
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $customType,
        public array $payload = [],
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'custom';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'custom_type' => $this->customType,
            'payload' => $this->payload,
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
