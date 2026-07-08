<?php

namespace Elqora\Dgp\Charges\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Charges\ChargeUpdateRequest;

interface ChargeUpdateHookContract
{
     /**
      * Push one or more charge updates to the host.
      *
      * @param ChargeUpdateRequest $request
      * @return Result<null>
      */
     public function update(ChargeUpdateRequest $request): Result;
}
