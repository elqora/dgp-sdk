<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionValidator;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Interactions\Contracts\Interaction;
use InvalidArgumentException;
use JsonSerializable;

abstract readonly class Delivery implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     * @param list<\Elqora\Dgp\Actions\ActionButton> $buttons
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
        public ?Interaction $nextAction = null,
        public array $meta = [],
        public string $kind = 'default',
        public ?string $name = null,
        public bool $isPublic = true,
        public ?string $note = null,
        public array $buttons = [],
    ) {
        $errors = ActionValidator::validateButtons($buttons);

        if ($errors !== []) {
            throw new InvalidArgumentException(reset($errors));
        }

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
            'buttons' => array_map(fn (ActionButton $button) => $button->toArray(), $this->buttons),
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
