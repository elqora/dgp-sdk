<?php

namespace Elqora\Dgp\Management;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class OrderManagement implements Arrayable, JsonSerializable
{
    /**
     * @param list<ManagementSection> $sections
     * @param list<ManagementWarning> $warnings
     * @param list<ManagementInstruction> $instructions
     * @param list<ManagementPermission> $permissions
     * @param list<\Elqora\Dgp\Actions\Contracts\NextAction> $actions
     * @param array<string, mixed> $refreshPolicy
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public array $sections = [],
        public array $warnings = [],
        public array $instructions = [],
        public array $permissions = [],
        public array $actions = [],
        public array $refreshPolicy = [],
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'sections' => array_map(fn ($s) => $s->toArray(), $this->sections),
            'warnings' => array_map(fn ($w) => $w->toArray(), $this->warnings),
            'instructions' => array_map(fn ($i) => $i->toArray(), $this->instructions),
            'permissions' => array_map(fn ($p) => $p->toArray(), $this->permissions),
            'actions' => array_map(fn ($a) => $a->toArray(), $this->actions),
            'refresh_policy' => $this->refreshPolicy,
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
