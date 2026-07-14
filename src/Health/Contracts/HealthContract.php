<?php

namespace Elqora\Dgp\Health\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Health\HandlerHealth;
use Elqora\Dgp\Health\HealthRequest;

interface HealthContract
{
    /**
     * Run a health check on the handler/provider.
     *
     * @param HealthRequest|null $request
     * @return Result<HandlerHealth>
     */
    public function health(?HealthRequest $request = null): Result;
}
