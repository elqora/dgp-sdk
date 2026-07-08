<?php

namespace Elqora\Dgp\Management;

use Elqora\Dgp\Snapshots\OrderSnapshot;
use Elqora\Dgp\Runtime\Plan;
use Elqora\Dgp\Charges\OrderChargeState;

final readonly class ResolveOrderManagementRequest
{
    /**
     * @param list<\Elqora\Dgp\Deliveries\InitializationDelivery> $initializationDeliveries
     * @param list<\Elqora\Dgp\Deliveries\FulfillmentDelivery> $fulfillmentDeliveries
     * @param list<\Elqora\Dgp\Charges\ChargeStatusView> $charges
     * @param array<string, mixed> $context
     */
    public function __construct(
        public OrderSnapshot $snapshot,
        public Plan $plan,
        public array $initializationDeliveries = [],
        public array $fulfillmentDeliveries = [],
        public array $charges = [],
        public ?OrderChargeState $chargeState = null,
        public array $context = [],
    ) {}
}
