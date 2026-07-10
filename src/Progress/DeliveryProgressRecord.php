<?php

namespace Elqora\Dgp\Progress;

use Elqora\Dgp\Deliveries\DeliveryProgress;
use Elqora\Dgp\Deliveries\DeliveryStage;
use Elqora\Dgp\Runtime\References\DeliveryReference;
use Elqora\Dgp\Support\Arrayable;
use JsonSerializable;

final readonly class DeliveryProgressRecord implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public string|int|null $id,
        public string|int $orderId,
        public DeliveryReference $delivery,
        public DeliveryStage $stage,
        public DeliveryProgress $progress,
        public string $recordedAt,
        public ?ProgressSource $source = null,
        public ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'delivery' => $this->delivery->toArray(),
            'stage' => $this->stage->value,
            'progress' => $this->progress->toArray(),
            'recorded_at' => $this->recordedAt,
            'source' => $this->source?->value,
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
