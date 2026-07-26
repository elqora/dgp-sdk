<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionValidator;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Runtime\StartResultStatus;
use Elqora\Interactions\Contracts\Interaction;
use InvalidArgumentException;
use JsonSerializable;

final readonly class StartResult implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $state
     * @param list<\Elqora\Dgp\Deliveries\FulfillmentDelivery> $deliveries
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
        public string|int|null $planId = null,
        public ?string $planKey = null,
        public int $revision = 0,
        public StartResultStatus $status = StartResultStatus::RUNNING,
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
            'plan_id' => $this->planId,
            'plan_key' => $this->planKey,
            'revision' => $this->revision,
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
