<?php

namespace Elqora\Dgp\Management;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class ManagementPermission implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $action, // e.g. 'cancel', 'refill', 'approve'
        public bool $allowed = true,
        public ?string $reason = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'allowed' => $this->allowed,
            'reason' => $this->reason,
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
