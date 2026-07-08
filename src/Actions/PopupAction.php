<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class PopupAction implements NextAction
{
    /**
     * @param array<string, mixed> $uiProps
     * @param array<string, mixed> $clientConfig
     */
    public function __construct(
        public string $uiEntry,
        public array $uiProps = [],
        public array $clientConfig = [],
        public ?PopupSpec $popup = null,
    ) {}

    public function type(): string
    {
        return 'popup';
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
            'popup' => $this->popup?->toArray(),
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
