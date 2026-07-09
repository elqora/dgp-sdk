<?php

namespace Elqora\Dgp\Deliveries\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\Queries\DeliveryQuery;
use Elqora\Dgp\Runtime\References\DeliveryReference;

interface HandlerDeliveriesRepositoryContract
{
    /**
     * @param DeliveryReference $reference
     * @return Result<\Elqora\Dgp\Deliveries\Delivery|null>
     */
    public function findDelivery(DeliveryReference $reference): Result;

    /**
     * @param DeliveryQuery|null $query
     * @return Result<list<\Elqora\Dgp\Deliveries\Delivery>>
     */
    public function deliveries(?DeliveryQuery $query = null): Result;
}
