<?php

namespace Elqora\Dgp\Deliveries;

use InvalidArgumentException;
use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class InitializationDelivery extends Delivery
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string|int|null $id,
        string $key,
        DeliveryStatus $status,
        string $label,
        mixed $progress = null,
        string|int|null $planId = null,
        ?string $startId = null,
        ?NextAction $nextAction = null,
        array $meta = [],
        string $kind = 'default',
        ?string $name = null,
        bool $isPublic = true,
        ?string $note = null,
    ) {
        if ($startId !== null) {
            throw new InvalidArgumentException('InitializationDelivery cannot have a startId.');
        }

        parent::__construct(
            id: $id,
            key: $key,
            stage: DeliveryStage::INITIALIZATION,
            status: $status,
            label: $label,
            progress: $progress,
            planId: $planId,
            startId: null,
            nextAction: $nextAction,
            meta: $meta,
            kind: $kind,
            name: $name,
            isPublic: $isPublic,
            note: $note,
        );
    }
}
