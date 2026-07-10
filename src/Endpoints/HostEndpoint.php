<?php

namespace Elqora\Dgp\Endpoints;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class HostEndpoint implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public HostEndpointType $type,
        public string $handler,
        public string $path,
        public array $parameters = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'handler' => $this->handler,
            'path' => $this->path,
            'parameters' => $this->parameters,
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
