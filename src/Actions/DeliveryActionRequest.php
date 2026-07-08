<?php

namespace Elqora\Dgp\Actions;

final readonly class DeliveryActionRequest
{
    /**
     * @param array<string, mixed> $actorContext
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public string $deliveryKey,
        public string $actionValue,
        public mixed $submittedInput = null,
        public ?string $deliveryId = null,
        public array $actorContext = [],
        public array $meta = [],
    ) {}
}
