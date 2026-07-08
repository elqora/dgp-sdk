<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class RedirectAction implements NextAction
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $url,
        public bool $external = true,
        public ?string $label = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'redirect';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'url' => $this->url,
            'external' => $this->external,
            'label' => $this->label,
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
