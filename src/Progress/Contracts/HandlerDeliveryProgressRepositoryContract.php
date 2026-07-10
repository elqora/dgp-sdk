<?php

namespace Elqora\Dgp\Progress\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Progress\DeliveryProgressRecord;
use Elqora\Dgp\Progress\ProgressTimelineQuery;
use Elqora\Dgp\Runtime\References\DeliveryReference;

interface HandlerDeliveryProgressRepositoryContract
{
    /**
     * @return Result<DeliveryProgressRecord>
     */
    public function record(
        DeliveryProgressRecord $record,
    ): Result;

    /**
     * @return Result<list<DeliveryProgressRecord>>
     */
    public function timeline(
        DeliveryReference $delivery,
        ?ProgressTimelineQuery $query = null,
    ): Result;

    /**
     * @return Result<list<DeliveryProgressRecord>>
     */
    public function timelineForOrder(
        string|int $orderId,
        ?ProgressTimelineQuery $query = null,
    ): Result;
}
