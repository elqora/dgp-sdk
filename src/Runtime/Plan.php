<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionValidator;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Runtime\PlanStatus;
use Elqora\Interactions\Contracts\Interaction;
use InvalidArgumentException;
use JsonSerializable;

final readonly class Plan implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $state
     * @param list<\Elqora\Dgp\Deliveries\InitializationDelivery> $deliveries
     * @param array<string, mixed> $meta
     * @param list<\Elqora\Dgp\Actions\ActionButton> $buttons
     */
    public function __construct(
        public string|int|null $id,
        public string $key,
        public array $state,
        public array $deliveries = [],
        public ?Interaction $nextAction = null,
        public array $meta = [],
        public int $revision = 0,
        public string|int|null $orderId = null,
        public PlanStatus $status = PlanStatus::ACTIVE,
        public array $buttons = [],
    ) {
        $errors = ActionValidator::validateButtons($buttons);

        if ($errors !== []) {
            throw new InvalidArgumentException(reset($errors));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'state' => $this->state,
            'status' => $this->status->value,
            'deliveries' => array_map(fn ($d) => $d->toArray(), $this->deliveries),
            'buttons' => array_map(fn (ActionButton $button) => $button->toArray(), $this->buttons),
            'next_action' => $this->nextAction?->toArray(),
            'meta' => $this->meta,
            'revision' => $this->revision,
            'order_id' => $this->orderId,
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
