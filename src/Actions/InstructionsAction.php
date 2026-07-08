<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class InstructionsAction implements NextAction
{
    /**
     * @param list<string> $steps
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $title,
        public array $steps = [],
        public ?string $description = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'instructions';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title,
            'steps' => $this->steps,
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
