<?php

namespace Elqora\Dgp\Bulk\Contracts;

use Elqora\Dgp\Bulk\CancelBulkRequest;
use Elqora\Dgp\Bulk\RefreshBulkRequest;
use Elqora\Dgp\Bulk\RetryBulkRequest;
use Elqora\Dgp\Bulk\StartBulkRequest;
use Elqora\Dgp\Errors\Result;

interface BulkActionContract
{
    /**
     * @return Result<null>
     */
    public function startBulk(StartBulkRequest $request): Result;

    /**
     * @return Result<null>
     */
    public function cancelBulk(CancelBulkRequest $request): Result;

    /**
     * @return Result<null>
     */
    public function retryBulk(RetryBulkRequest $request): Result;

    /**
     * @return Result<null>
     */
    public function refreshBulk(RefreshBulkRequest $request): Result;
}
