<?php

namespace Elqora\Dgp\Runtime;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionValidator;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Interactions\Contracts\Interaction;
use InvalidArgumentException;
use JsonSerializable;

final readonly class PreparationResult implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $state
     * @param list<\Elqora\Dgp\Deliveries\InitializationDelivery> $deliveries
     * @param array<string, mixed> $meta
     * @param list<\Elqora\Dgp\Actions\ActionButton> $buttons
     */
    public function __construct(
        public string|int $planId,
        public PreparationStatus $status,
        public array $deliveries = [],
        public ?Interaction $nextAction = null,
        public array $state = [],
        public array $meta = [],
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
            'plan_id' => $this->planId,
            'status' => $this->status->value,
            'deliveries' => array_map(fn ($d) => $d->toArray(), $this->deliveries),
            'buttons' => array_map(fn (ActionButton $button) => $button->toArray(), $this->buttons),
            'next_action' => $this->nextAction?->toArray(),
            'state' => $this->state,
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
