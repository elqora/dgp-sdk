<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

abstract readonly class Delivery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public DeliveryStage $stage,
        public DeliveryStatus $status,
        public string $label,
        public string|int|float|null $progress = null,
        public string|int|null $planId = null,
        public string|int|null $startId = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'stage' => $this->stage->value,
            'status' => $this->status->value,
            'label' => $this->label,
            'progress' => $this->progress,
            'plan_id' => $this->planId,
            'start_id' => $this->startId,
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
