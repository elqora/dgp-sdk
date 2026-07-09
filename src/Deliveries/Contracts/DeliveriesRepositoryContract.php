<?php

namespace Elqora\Dgp\Deliveries\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\References\HandlerReference;

interface DeliveriesRepositoryContract
{
    /**
     * Resolve a deliveries repository permanently scoped to one handler.
     *
     * @param HandlerReference $handler
     * @return Result<HandlerDeliveriesRepositoryContract>
     */
    public function forHandler(HandlerReference $handler): Result;
}
