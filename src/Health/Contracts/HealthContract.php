<?php

namespace Elqora\Dgp\Health\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Health\HandlerHealth;

interface HealthContract
{
    /**
     * Run a health check on the handler/provider.
     *
     * @return Result<HandlerHealth>
     */
    public function health(): Result;
}
