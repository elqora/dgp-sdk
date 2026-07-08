<?php

namespace Elqora\Dgp\Deliveries\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Deliveries\ResolveOrderDeliveriesRequest;

interface OrderDeliveriesContract
{
    /**
     * Resolve the active deliveries for an order.
     *
     * @param ResolveOrderDeliveriesRequest $request
     * @return Result<\Elqora\Dgp\Deliveries\Delivery[]>
     */
    public function resolveDeliveries(ResolveOrderDeliveriesRequest $request): Result;
}
