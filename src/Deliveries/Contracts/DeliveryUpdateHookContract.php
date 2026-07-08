<?php

namespace Elqora\Dgp\Deliveries\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Deliveries\DeliveryUpdateRequest;

interface DeliveryUpdateHookContract
{
     /**
      * Push one or more delivery updates to the host.
      *
      * @param DeliveryUpdateRequest $request
      * @return Result<null>
      */
     public function update(DeliveryUpdateRequest $request): Result;
}
