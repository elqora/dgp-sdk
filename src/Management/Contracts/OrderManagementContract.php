<?php

namespace Elqora\Dgp\Management\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Management\ResolveOrderManagementRequest;

interface OrderManagementContract
{
    /**
     * Resolve the current order-management projection.
     *
     * @param ResolveOrderManagementRequest $request
     * @return Result<\Elqora\Dgp\Management\OrderManagement>
     */
    public function resolveManagement(ResolveOrderManagementRequest $request): Result;
}
