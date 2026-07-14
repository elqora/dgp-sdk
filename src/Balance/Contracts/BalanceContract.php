<?php

namespace Elqora\Dgp\Balance\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Balance\HandlerBalance;
use Elqora\Dgp\Balance\BalanceRequest;

interface BalanceContract
{
    /**
     * Get the handler balance.
     *
     * @param BalanceRequest|null $request
     * @return Result<HandlerBalance>
     */
    public function balance(?BalanceRequest $request = null): Result;
}
