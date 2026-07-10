<?php

namespace Elqora\Dgp\Deliveries;

use InvalidArgumentException;
use Elqora\Dgp\Actions\Contracts\NextAction;

final readonly class FulfillmentDelivery extends Delivery
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
        ?string $planId = null,
        string|int|null $startId = null,
        ?NextAction $nextAction = null,
        array $meta = [],
        string $kind = 'default',
        ?string $name = null,
        bool $isPublic = true,
        ?string $note = null,
    ) {
        if ($planId !== null) {
            throw new InvalidArgumentException('FulfillmentDelivery cannot have a planId.');
        }

        parent::__construct(
            id: $id,
            key: $key,
            stage: DeliveryStage::FULFILLMENT,
            status: $status,
            label: $label,
            progress: $progress,
            planId: null,
            startId: $startId,
            nextAction: $nextAction,
            meta: $meta,
            kind: $kind,
            name: $name,
            isPublic: $isPublic,
            note: $note,
        );
    }
}
