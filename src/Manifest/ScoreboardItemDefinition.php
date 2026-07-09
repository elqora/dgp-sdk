<?php

namespace Elqora\Dgp\Manifest;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Support\StableIdentifier;
use InvalidArgumentException;
use JsonSerializable;

final readonly class ScoreboardItemDefinition implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $key,
        public string $title,
        public ?string $description = null,
    ) {
        StableIdentifier::assert($this->key, 'Scoreboard item definition key');

        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Scoreboard item definition title must not be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
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
