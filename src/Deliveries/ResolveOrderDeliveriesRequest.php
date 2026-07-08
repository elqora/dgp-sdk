<?php

namespace Elqora\Dgp\Deliveries;

final readonly class ResolveOrderDeliveriesRequest
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string|int $orderId,
        public array $meta = [],
    ) {}
}
