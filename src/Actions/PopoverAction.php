<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class PopoverAction implements NextAction
{
    /**
     * @param array<string, mixed> $uiProps
     * @param array<string, mixed> $clientConfig
     */
    public function __construct(
        public string $uiEntry,
        public array $uiProps = [],
        public array $clientConfig = [],
        public ?string $anchor = null,
        public ?PopoverSpec $popover = null,
    ) {}

    public function type(): string
    {
        return 'popover';
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
            'anchor' => $this->anchor,
            'popover' => $this->popover?->toArray(),
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
