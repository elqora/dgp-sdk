<?php

namespace Elqora\Dgp\Charges\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Charges\ChargeStateRequest;

interface ChargeStateResolverContract
{
    /**
     * Resolve the current charge and payment state.
     *
     * @param ChargeStateRequest $request
     * @return Result<\Elqora\Dgp\Charges\OrderChargeState>
     */
    public function resolve(ChargeStateRequest $request): Result;
}
