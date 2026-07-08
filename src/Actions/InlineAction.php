<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class InlineAction implements NextAction
{
    /**
     * @param array<string, mixed> $uiProps
     * @param array<string, mixed> $clientConfig
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $uiEntry,
        public array $uiProps = [],
        public array $clientConfig = [],
        public ?string $containerKey = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'inline';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'ui_entry' => $this->uiEntry,
            'ui_props' => $this->uiProps,
            'client_config' => $this->clientConfig,
            'container_key' => $this->containerKey,
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
