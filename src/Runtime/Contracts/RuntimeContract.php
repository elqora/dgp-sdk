<?php

namespace Elqora\Dgp\Runtime\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Runtime\InitializeRequest;
use Elqora\Dgp\Runtime\StartRequest;
use Elqora\Dgp\Runtime\SynchronizeRequest;
use Elqora\Dgp\Runtime\CancelRequest;

interface RuntimeContract
{
    /**
     * Initialize an order.
     *
     * @param InitializeRequest $request
     * @return Result<\Elqora\Dgp\Runtime\Plan>
     */
    public function initialize(InitializeRequest $request): Result;

    /**
     * Start order fulfillment.
     *
     * @param StartRequest $request
     * @return Result<\Elqora\Dgp\Runtime\StartResult>
     */
    public function start(StartRequest $request): Result;

    /**
     * Synchronize order state.
     *
     * @param SynchronizeRequest $request
     * @return Result<null>
     */
    public function synchronize(SynchronizeRequest $request): Result;

    /**
     * Cancel order fulfillment.
     *
     * @param CancelRequest $request
     * @return Result<null>
     */
    public function cancel(CancelRequest $request): Result;
}
