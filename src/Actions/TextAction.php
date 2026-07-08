<?php

namespace Elqora\Dgp\Actions;

use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class TextAction implements NextAction
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $content,
        public string $severity = 'info', // info, warning, error, success
        public ?string $title = null,
        public array $meta = [],
    ) {}

    public function type(): string
    {
        return 'text';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'content' => $this->content,
            'severity' => $this->severity,
            'title' => $this->title,
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
