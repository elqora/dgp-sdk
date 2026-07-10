<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Runtime\RuntimeContext;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class GenericActionRequest implements Arrayable, JsonSerializable
{
    /**
     * @param list<ActionTarget> $targets
     * @param array<string, mixed>|null $input
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public string $handlerKey,
        public string $actionValue,
        public array $targets,
        public ?array $input = null,
        public ?RuntimeContext $context = null,
        public ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'handler_key' => $this->handlerKey,
            'action_value' => $this->actionValue,
            'targets' => array_map(fn (ActionTarget $target) => $target->toArray(), $this->targets),
            'input' => $this->input,
            'context' => $this->context?->toArray(),
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
