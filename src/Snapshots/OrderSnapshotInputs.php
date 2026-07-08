<?php

namespace Elqora\Dgp\Snapshots;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderSnapshotInputs implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $form
     * @param array<string, list<string>> $selections
     */
    public function __construct(
        public array $form = [],
        public array $selections = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'form' => $this->form,
            'selections' => $this->selections,
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
