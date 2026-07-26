<?php

namespace Elqora\Dgp\Deliveries;

use Elqora\Dgp\Actions\ActionButton;
use Elqora\Dgp\Actions\ActionValidator;
use Elqora\Dgp\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

final readonly class DeliveryProgressSegment implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed>|null $meta
     * @param list<\Elqora\Dgp\Actions\ActionButton> $buttons
     */
    public function __construct(
        public string $key,
        mixed $progress,
        public ?string $label = null,
        public ?string $status = null,
        public int|float|null $sequence = null,
        public ?array $meta = null,
        public bool $isPublic = true,
        public array $buttons = [],
    ) {
        $errors = ActionValidator::validateButtons($buttons);

        if ($errors !== []) {
            throw new InvalidArgumentException(reset($errors));
        }

        $this->progress = DeliveryProgress::fromSegmentValue($progress) ?? new DeliveryProgress();
    }

    public DeliveryProgress $progress;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'progress' => $this->progress->toArray(),
            'label' => $this->label,
            'status' => $this->status,
            'sequence' => $this->sequence,
            'meta' => $this->meta,
            'is_public' => $this->isPublic,
            'buttons' => array_map(fn (ActionButton $button): array => $button->toArray(), $this->buttons),
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
