<?php

namespace Elqora\Dgp\Actions\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Actions\DeliveryActionRequest;

interface DeliveryActionContract
{
     /**
      * Handle a delivery action routed from the host.
      *
      * @param DeliveryActionRequest $request
      * @return Result<null>
      */
     public function handle(DeliveryActionRequest $request): Result;
}
