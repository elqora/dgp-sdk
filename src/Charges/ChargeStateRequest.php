<?php

namespace Elqora\Dgp\Charges;

final readonly class ChargeStateRequest
{
    public function __construct(
        public string|int $orderId,
        public string|int|null $chargeId = null,
        public ?string $chargeKey = null,
        public ?string $deliveryKey = null,
    ) {}
}
