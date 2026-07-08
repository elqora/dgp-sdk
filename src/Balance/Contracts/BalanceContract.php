<?php

namespace Elqora\Dgp\Balance\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Balance\HandlerBalance;

interface BalanceContract
{
    /**
     * Get the handler balance.
     *
     * @return Result<HandlerBalance>
     */
    public function balance(): Result;
}
