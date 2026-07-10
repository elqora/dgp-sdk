<?php

namespace Elqora\Dgp\Progress\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

interface DeliveryProgressRepositoryContract
{
    /**
     * @return Result<HandlerDeliveryProgressRepositoryContract>
     */
    public function forHandler(
        HandlerReference $handler
    ): Result;
}
