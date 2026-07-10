<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Actions\Contracts\NextAction;
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
        mixed $progress = null,
        public string|int|null $planId = null,
        public string|int|null $startId = null,
        public ?NextAction $nextAction = null,
        public array $meta = [],
        public string $kind = 'default',
        public ?string $name = null,
        public bool $isPublic = true,
        public ?string $note = null,
    ) {
        $this->progress = DeliveryProgress::fromValue($progress);
    }

    public ?DeliveryProgress $progress;

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
            'kind' => $this->kind,
            'name' => $this->name,
            'is_public' => $this->isPublic,
            'note' => $this->note,
            'progress' => $this->progress?->toArray(),
            'plan_id' => $this->planId,
            'start_id' => $this->startId,
            'next_action' => $this->nextAction?->toArray(),
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
